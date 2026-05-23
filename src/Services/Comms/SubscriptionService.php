<?php

namespace Platform\Crm\Services\Comms;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Platform\Crm\Models\CommsChannel;
use Platform\Crm\Models\CommsUnsubscribe;
use Platform\Crm\Models\CrmContact;
use Platform\Crm\Models\CrmContactList;
use Platform\Crm\Models\CrmContactListMember;
use Platform\Crm\Events\ContactListSubscriptionChanged;

class SubscriptionService
{
    /**
     * Subscribe a contact to a list.
     * If the list requires DOI and source is not manual_admin, sends a confirmation email.
     * Re-subscribes existing unsubscribed members.
     */
    public function subscribe(CrmContactList $list, CrmContact $contact, string $source = 'manual_admin', ?int $userId = null): CrmContactListMember
    {
        $member = CrmContactListMember::where('contact_list_id', $list->id)
            ->where('contact_id', $contact->id)
            ->first();

        if ($member) {
            // Already subscribed — no-op
            if ($member->isSubscribed()) {
                return $member;
            }

            // Already pending DOI — no-op
            if ($member->isPendingDoi()) {
                return $member;
            }

            // Re-subscribe from unsubscribed
            if ($list->requires_doi && $source !== 'manual_admin') {
                $token = Str::random(64);
                $member->update([
                    'status' => 'pending_doi',
                    'doi_token' => $token,
                    'unsubscribed_at' => null,
                    'consent_source' => $source,
                    'added_by_user_id' => $userId,
                ]);
                $this->sendDoiConfirmationEmail($member);
            } else {
                $member->update([
                    'status' => 'subscribed',
                    'subscribed_at' => now(),
                    'unsubscribed_at' => null,
                    'consent_source' => $source,
                    'opt_in_confirmed_at' => now(),
                    'doi_token' => null,
                    'added_by_user_id' => $userId,
                ]);
            }

            $list->updateMemberCount();
            return $member->fresh();
        }

        // New member
        $data = [
            'contact_list_id' => $list->id,
            'contact_id' => $contact->id,
            'added_by_user_id' => $userId,
            'consent_source' => $source,
        ];

        if ($list->requires_doi && $source !== 'manual_admin') {
            $token = Str::random(64);
            $data['status'] = 'pending_doi';
            $data['doi_token'] = $token;
        } else {
            $data['status'] = 'subscribed';
            $data['subscribed_at'] = now();
            $data['opt_in_confirmed_at'] = now();
        }

        $member = CrmContactListMember::create($data);

        if ($member->isPendingDoi()) {
            $this->sendDoiConfirmationEmail($member);
        }

        $list->updateMemberCount();

        ContactListSubscriptionChanged::dispatch($member, 'subscribed');

        return $member;
    }

    /**
     * Unsubscribe a member from their list.
     */
    public function unsubscribe(CrmContactListMember $member, ?string $reason = null): void
    {
        $member->update([
            'status' => 'unsubscribed',
            'unsubscribed_at' => now(),
            'consent_source' => $reason ?? 'user_unsubscribed',
            'doi_token' => null,
        ]);

        $member->contactList?->updateMemberCount();

        ContactListSubscriptionChanged::dispatch($member, 'unsubscribed');
    }

    /**
     * Unsubscribe by email + list ID.
     */
    public function unsubscribeFromList(int $teamId, string $email, int $listId, ?string $reason = null): void
    {
        $member = $this->findMemberByEmail($teamId, $email, $listId);

        if ($member) {
            $this->unsubscribe($member, $reason);
        }
    }

    /**
     * Unsubscribe from all lists for a given email in a team.
     */
    public function unsubscribeAllListsForEmail(int $teamId, string $email, ?string $reason = null): void
    {
        $emailLower = strtolower(trim($email));

        $members = CrmContactListMember::query()
            ->whereIn('contact_id', function ($q) use ($teamId, $emailLower) {
                $q->select('crm_contacts.id')
                    ->from('crm_contacts')
                    ->join('crm_contact_email_addresses', 'crm_contacts.id', '=', 'crm_contact_email_addresses.contact_id')
                    ->where('crm_contacts.team_id', $teamId)
                    ->whereRaw('LOWER(crm_contact_email_addresses.email) = ?', [$emailLower]);
            })
            ->whereIn('status', ['subscribed', 'pending_doi'])
            ->get();

        foreach ($members as $member) {
            $this->unsubscribe($member, $reason ?? 'global_unsubscribe');
        }
    }

    /**
     * Global unsubscribe: adds to CommsUnsubscribe + unsubscribes all list memberships.
     */
    public function globalUnsubscribe(int $teamId, string $email, ?string $reason = null): void
    {
        app(NewsletterService::class)->handleUnsubscribe($teamId, $email, $reason);
        $this->unsubscribeAllListsForEmail($teamId, $email, $reason ?? 'global_unsubscribe');
    }

    /**
     * Resubscribe globally: removes CommsUnsubscribe entry.
     * Does NOT automatically re-subscribe to individual lists.
     */
    public function resubscribeGlobal(int $teamId, string $email): void
    {
        $emailLower = strtolower(trim($email));

        CommsUnsubscribe::where('team_id', $teamId)
            ->whereRaw('LOWER(email_address) = ?', [$emailLower])
            ->delete();
    }

    /**
     * Confirm a DOI token.
     */
    public function confirmDoi(string $token): ?CrmContactListMember
    {
        $member = CrmContactListMember::where('doi_token', $token)
            ->where('status', 'pending_doi')
            ->first();

        if (!$member) {
            return null;
        }

        $member->update([
            'status' => 'subscribed',
            'subscribed_at' => now(),
            'opt_in_confirmed_at' => now(),
            'consent_source' => 'doi_confirmed',
            'doi_token' => null,
        ]);

        $member->contactList?->updateMemberCount();

        ContactListSubscriptionChanged::dispatch($member, 'doi_confirmed');

        return $member->fresh();
    }

    /**
     * Send DOI confirmation email for a pending member.
     */
    public function sendDoiConfirmationEmail(CrmContactListMember $member): void
    {
        $member->loadMissing(['contact.emailAddresses', 'contactList']);

        $contact = $member->contact;
        $list = $member->contactList;

        if (!$contact || !$list) {
            return;
        }

        $email = $contact->emailAddresses()
            ->where('is_primary', true)
            ->first()?->email
            ?? $contact->emailAddresses()->first()?->email;

        if (!$email) {
            return;
        }

        // Find an email channel for this team
        $channel = CommsChannel::where('team_id', $list->team_id)
            ->where('type', 'email')
            ->where('provider', 'postmark')
            ->where('is_active', true)
            ->first();

        if (!$channel) {
            return;
        }

        $confirmUrl = route('crm.newsletter.doi-confirm', ['token' => $member->doi_token]);

        $subject = $list->doi_confirmation_subject ?: 'Bitte bestätigen Sie Ihre Anmeldung';
        $body = $list->doi_confirmation_body;

        if ($body) {
            $htmlBody = str_replace('{{CONFIRMATION_URL}}', $confirmUrl, $body);
        } else {
            $htmlBody = $this->getDefaultDoiEmailHtml($confirmUrl, $list->name);
        }

        try {
            app(PostmarkEmailService::class)->sendRaw(
                $channel,
                $email,
                $subject,
                $htmlBody,
                null,
                [
                    'tag' => 'doi_confirmation',
                    'metadata' => [
                        'type' => 'doi_confirmation',
                        'list_id' => (string) $list->id,
                        'member_id' => (string) $member->id,
                    ],
                ]
            );
        } catch (\Throwable $e) {
            \Log::warning('[SubscriptionService] Failed to send DOI email', [
                'member_id' => $member->id,
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get preference center data for an email address in a team.
     */
    public function getPreferenceCenterData(int $teamId, string $email): array
    {
        $emailLower = strtolower(trim($email));

        $isGloballyUnsubscribed = CommsUnsubscribe::where('team_id', $teamId)
            ->whereRaw('LOWER(email_address) = ?', [$emailLower])
            ->exists();

        $lists = CrmContactList::where('team_id', $teamId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // Find contact IDs for this email
        $contactIds = \DB::table('crm_contacts')
            ->join('crm_contact_email_addresses', 'crm_contacts.id', '=', 'crm_contact_email_addresses.contact_id')
            ->where('crm_contacts.team_id', $teamId)
            ->whereRaw('LOWER(crm_contact_email_addresses.email) = ?', [$emailLower])
            ->pluck('crm_contacts.id')
            ->toArray();

        $memberships = [];
        if (!empty($contactIds)) {
            $memberships = CrmContactListMember::whereIn('contact_id', $contactIds)
                ->get()
                ->keyBy('contact_list_id');
        }

        $listSubscriptions = $lists->map(function (CrmContactList $list) use ($memberships) {
            $member = $memberships->get($list->id);
            return [
                'list_id' => $list->id,
                'name' => $list->name,
                'description' => $list->description,
                'color' => $list->color,
                'requires_doi' => $list->requires_doi,
                'status' => $member?->status ?? 'not_subscribed',
                'member_id' => $member?->id,
            ];
        })->toArray();

        return [
            'is_globally_unsubscribed' => $isGloballyUnsubscribed,
            'list_subscriptions' => $listSubscriptions,
        ];
    }

    /**
     * Generate a signed preference center URL.
     */
    public function generatePreferenceCenterUrl(int $teamId, string $email): string
    {
        return URL::signedRoute('crm.newsletter.preferences', [
            'team' => $teamId,
            'email' => $email,
        ]);
    }

    /**
     * Find a member by email + list.
     */
    private function findMemberByEmail(int $teamId, string $email, int $listId): ?CrmContactListMember
    {
        $emailLower = strtolower(trim($email));

        return CrmContactListMember::where('contact_list_id', $listId)
            ->whereIn('contact_id', function ($q) use ($teamId, $emailLower) {
                $q->select('crm_contacts.id')
                    ->from('crm_contacts')
                    ->join('crm_contact_email_addresses', 'crm_contacts.id', '=', 'crm_contact_email_addresses.contact_id')
                    ->where('crm_contacts.team_id', $teamId)
                    ->whereRaw('LOWER(crm_contact_email_addresses.email) = ?', [$emailLower]);
            })
            ->first();
    }

    /**
     * Default DOI email HTML template.
     */
    private function getDefaultDoiEmailHtml(string $confirmUrl, string $listName): string
    {
        $escapedUrl = e($confirmUrl);
        $escapedName = e($listName);

        return <<<HTML
<!DOCTYPE html>
<html lang="de">
<head><meta charset="UTF-8"></head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f5f5f5; padding: 40px 20px;">
    <div style="max-width: 480px; margin: 0 auto; background: #ffffff; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); padding: 48px 40px; text-align: center;">
        <h1 style="font-size: 20px; font-weight: 600; color: #111827; margin-bottom: 16px;">Anmeldung bestätigen</h1>
        <p style="font-size: 14px; color: #6b7280; line-height: 1.6; margin-bottom: 24px;">
            Sie haben sich für die Liste <strong>{$escapedName}</strong> angemeldet. Bitte bestätigen Sie Ihre Anmeldung mit dem folgenden Button:
        </p>
        <a href="{$escapedUrl}" style="display: inline-block; padding: 12px 32px; background-color: #10b981; color: #ffffff; font-size: 14px; font-weight: 600; text-decoration: none; border-radius: 8px;">
            Anmeldung bestätigen
        </a>
        <p style="font-size: 12px; color: #9ca3af; margin-top: 24px; line-height: 1.5;">
            Falls Sie sich nicht angemeldet haben, können Sie diese E-Mail ignorieren.
        </p>
    </div>
</body>
</html>
HTML;
    }
}
