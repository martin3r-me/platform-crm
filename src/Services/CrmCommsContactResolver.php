<?php

namespace Platform\Crm\Services;

use Illuminate\Support\Facades\Log;
use Platform\Core\Contracts\CommsContactResolverInterface;
use Platform\Crm\Models\CrmContact;
use Platform\Crm\Models\CrmEmailAddress;
use Platform\Crm\Models\CrmPhoneNumber;

/**
 * CRM implementation of CommsContactResolverInterface.
 *
 * Resolves contacts from phone numbers and email addresses
 * by looking up CrmPhoneNumber and CrmEmailAddress records.
 */
class CrmCommsContactResolver implements CommsContactResolverInterface
{
    /**
     * CRM has high priority (100) as the primary contact source.
     */
    public function priority(): int
    {
        return 100;
    }

    /**
     * Resolve a contact by phone number.
     *
     * @param string $phone E.164 format phone number (e.g., "+4915112345678")
     * @return array|null ['model' => Model, 'type' => string, 'id' => int, 'display_name' => string]
     */
    public function resolveByPhone(string $phone): ?array
    {
        // Normalize phone number (remove spaces, ensure + prefix)
        $normalizedPhone = $this->normalizePhone($phone);

        // Search in CrmPhoneNumber by international format
        $phoneRecord = CrmPhoneNumber::query()
            ->where('is_active', true)
            ->where(function ($query) use ($normalizedPhone, $phone) {
                // Try exact match on international field
                $query->where('international', $normalizedPhone)
                    ->orWhere('international', $phone)
                    // Also try without + prefix
                    ->orWhere('international', ltrim($normalizedPhone, '+'))
                    ->orWhere('international', '+' . ltrim($phone, '+'));
            })
            ->first();

        if (!$phoneRecord) {
            return null;
        }

        $contact = $phoneRecord->phoneable;
        if (!$contact) {
            return null;
        }

        return $this->formatResult($contact, $phoneRecord);
    }

    /**
     * Resolve a contact by email address.
     *
     * @param string $email Email address
     * @return array|null ['model' => Model, 'type' => string, 'id' => int, 'display_name' => string]
     */
    public function resolveByEmail(string $email): ?array
    {
        $normalizedEmail = strtolower(trim($email));

        $emailRecord = CrmEmailAddress::query()
            ->where('is_active', true)
            ->whereRaw('LOWER(email_address) = ?', [$normalizedEmail])
            ->first();

        if (!$emailRecord) {
            return null;
        }

        $contact = $emailRecord->emailable;
        if (!$contact) {
            return null;
        }

        return $this->formatResult($contact, $emailRecord);
    }

    /**
     * Create a new contact from phone number.
     *
     * @param string $phone E.164 format phone number
     * @param array $meta Additional metadata (source, channel_id, team_id, etc.)
     * @return array|null ['model' => Model, 'type' => string, 'id' => int, 'display_name' => string]
     */
    public function createFromPhone(string $phone, array $meta = []): ?array
    {
        $teamId = $meta['team_id'] ?? null;
        if (!$teamId) {
            Log::warning('[CrmCommsContactResolver] Cannot create contact: team_id missing', [
                'phone' => $phone,
                'meta' => $meta,
            ]);
            return null;
        }

        $normalizedPhone = $this->normalizePhone($phone);

        try {
            // Create the contact
            $contact = CrmContact::create([
                'first_name' => 'Unbekannt',
                'last_name' => $normalizedPhone,
                'team_id' => $teamId,
                'is_active' => true,
                'notes' => sprintf(
                    'Automatisch erstellt aus %s am %s',
                    $meta['source'] ?? 'Kommunikation',
                    now()->format('d.m.Y H:i')
                ),
            ]);

            // Get mobile phone type (or first available)
            $phoneTypeId = $this->getMobilePhoneTypeId();

            // Create the phone number
            $phoneRecord = $contact->phoneNumbers()->create([
                'raw_input' => $phone,
                'international' => $normalizedPhone,
                'phone_type_id' => $phoneTypeId,
                'is_primary' => true,
                'is_active' => true,
            ]);

            Log::info('[CrmCommsContactResolver] Contact created from phone', [
                'contact_id' => $contact->id,
                'phone' => $normalizedPhone,
                'source' => $meta['source'] ?? null,
            ]);

            return $this->formatResult($contact, $phoneRecord);
        } catch (\Throwable $e) {
            Log::error('[CrmCommsContactResolver] Failed to create contact from phone', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Create a new contact from email address.
     *
     * @param string $email Email address
     * @param array $meta Additional metadata (source, channel_id, team_id, etc.)
     * @return array|null ['model' => Model, 'type' => string, 'id' => int, 'display_name' => string]
     */
    public function createFromEmail(string $email, array $meta = []): ?array
    {
        $teamId = $meta['team_id'] ?? null;
        if (!$teamId) {
            Log::warning('[CrmCommsContactResolver] Cannot create contact: team_id missing', [
                'email' => $email,
                'meta' => $meta,
            ]);
            return null;
        }

        $normalizedEmail = strtolower(trim($email));

        try {
            // Try to extract name from email
            $nameParts = $this->extractNameFromEmail($normalizedEmail);

            // Create the contact
            $contact = CrmContact::create([
                'first_name' => $nameParts['first_name'],
                'last_name' => $nameParts['last_name'],
                'team_id' => $teamId,
                'is_active' => true,
                'notes' => sprintf(
                    'Automatisch erstellt aus %s am %s',
                    $meta['source'] ?? 'Kommunikation',
                    now()->format('d.m.Y H:i')
                ),
            ]);

            // Get default email type (or first available)
            $emailTypeId = $this->getDefaultEmailTypeId();

            // Create the email address
            $emailRecord = $contact->emailAddresses()->create([
                'email_address' => $normalizedEmail,
                'email_type_id' => $emailTypeId,
                'is_primary' => true,
                'is_active' => true,
            ]);

            Log::info('[CrmCommsContactResolver] Contact created from email', [
                'contact_id' => $contact->id,
                'email' => $normalizedEmail,
                'source' => $meta['source'] ?? null,
            ]);

            return $this->formatResult($contact, $emailRecord);
        } catch (\Throwable $e) {
            Log::error('[CrmCommsContactResolver] Failed to create contact from email', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Check if this resolver supports auto-creation of contacts.
     */
    public function supportsAutoCreate(): bool
    {
        return true;
    }

    /**
     * Format the result array.
     */
    private function formatResult($contact, $communicationRecord = null): array
    {
        $displayName = match (true) {
            $contact instanceof CrmContact => $contact->display_name ?? $contact->full_name ?? "{$contact->first_name} {$contact->last_name}",
            method_exists($contact, 'name') => $contact->name,
            isset($contact->name) => $contact->name,
            default => "Contact #{$contact->id}",
        };

        return [
            'model' => $contact,
            'type' => get_class($contact),
            'id' => $contact->id,
            'display_name' => trim($displayName),
            'communication_record' => $communicationRecord,
        ];
    }

    /**
     * Normalize phone number to E.164 format.
     */
    private function normalizePhone(string $phone): string
    {
        // Remove all non-numeric characters except +
        $cleaned = preg_replace('/[^\d+]/', '', $phone);

        // Ensure + prefix
        if (!str_starts_with($cleaned, '+')) {
            $cleaned = '+' . $cleaned;
        }

        return $cleaned;
    }

    /**
     * Get the mobile phone type ID.
     */
    private function getMobilePhoneTypeId(): ?int
    {
        // Try to find "Mobile" or "Mobil" type
        $type = \Platform\Crm\Models\CrmPhoneType::query()
            ->where(function ($query) {
                $query->where('name', 'like', '%mobil%')
                    ->orWhere('name', 'like', '%mobile%')
                    ->orWhere('name', 'like', '%handy%');
            })
            ->first();

        return $type?->id ?? \Platform\Crm\Models\CrmPhoneType::first()?->id;
    }

    /**
     * Get the default email type ID.
     */
    private function getDefaultEmailTypeId(): ?int
    {
        // Try to find "Private" or "Business" type
        $type = \Platform\Crm\Models\CrmEmailType::query()
            ->where(function ($query) {
                $query->where('name', 'like', '%privat%')
                    ->orWhere('name', 'like', '%private%')
                    ->orWhere('name', 'like', '%business%')
                    ->orWhere('name', 'like', '%geschäft%');
            })
            ->first();

        return $type?->id ?? \Platform\Crm\Models\CrmEmailType::first()?->id;
    }

    /**
     * Extract name parts from email address.
     */
    private function extractNameFromEmail(string $email): array
    {
        $localPart = explode('@', $email)[0] ?? '';

        // Common separators: . _ -
        $parts = preg_split('/[._\-]/', $localPart);

        if (count($parts) >= 2) {
            return [
                'first_name' => ucfirst($parts[0]),
                'last_name' => ucfirst($parts[1]),
            ];
        }

        return [
            'first_name' => 'Unbekannt',
            'last_name' => ucfirst($localPart) ?: $email,
        ];
    }
}
