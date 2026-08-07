<?php

namespace Platform\Crm\People;

use Platform\Crm\Models\CrmContact;
use Platform\People\Contracts\ContactDirectoryProvider;

/**
 * CrmContactDirectory — stellt dem people-Modul CRM-Kontakte zum Suchen/Anlegen bereit,
 * damit ein Mitarbeiter am Personen-Knoten mit einem crm_contact verknüpft werden kann.
 * Spiegelbild zu CrmCompanyDirectory (customer).
 */
class CrmContactDirectory implements ContactDirectoryProvider
{
    public function search(int $teamId, string $query): array
    {
        return CrmContact::query()
            ->where('team_id', $teamId)
            ->when($query !== '', function ($q) use ($query) {
                $q->where(function ($qq) use ($query) {
                    $qq->where('first_name', 'like', '%' . $query . '%')
                       ->orWhere('last_name', 'like', '%' . $query . '%');
                });
            })
            ->orderBy('last_name')->orderBy('first_name')
            ->limit(15)
            ->get()
            ->map(fn ($c) => [
                'id'       => (int) $c->id,
                'label'    => trim(($c->first_name ?? '') . ' ' . ($c->last_name ?? '')),
                'subtitle' => $c->emailAddresses()->value('email_address'),
            ])
            ->all();
    }

    public function createContact(int $teamId, string $name): ?int
    {
        $parts = preg_split('/\s+/', trim($name), 2);
        $first = $parts[0] ?? $name;
        $last  = $parts[1] ?? $first;

        $contact = CrmContact::create([
            'team_id'            => $teamId,
            'first_name'         => $first,
            'last_name'          => $last,
            'created_by_user_id' => auth()->id(),
            'owned_by_user_id'   => auth()->id(),
            'is_active'          => true,
        ]);

        return (int) $contact->id;
    }
}
