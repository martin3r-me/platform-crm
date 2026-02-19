<?php

namespace Platform\Crm\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Crm\Models\CrmContact;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

/**
 * Bulk-Tool zum Löschen mehrerer Contacts im CRM-Modul
 */
class BulkDeleteContactsTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'crm.contacts.bulk.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /contacts/bulk - Löscht mehrere Contacts gleichzeitig. Maximal 50 Contacts pro Aufruf. Beim Löschen werden auch alle zugehörigen Beziehungen gelöscht.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'ids' => [
                    'type' => 'array',
                    'description' => 'Array von Contact-IDs zum Löschen (max. 50). Nutze "crm.contacts.GET" um Contacts zu finden.',
                    'items' => [
                        'type' => 'integer',
                        'description' => 'Contact-ID'
                    ]
                ],
                'confirm' => [
                    'type' => 'boolean',
                    'description' => 'Bestätigung für das Bulk-Löschen (ERFORDERLICH). Frage den Nutzer explizit nach Bestätigung.',
                    'default' => false
                ]
            ],
            'required' => ['ids', 'confirm']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $ids = $arguments['ids'] ?? [];
            if (empty($ids)) {
                return ToolResult::error('VALIDATION_ERROR', 'ids-Array darf nicht leer sein.');
            }
            if (count($ids) > 50) {
                return ToolResult::error('VALIDATION_ERROR', 'Maximal 50 Contacts pro Bulk-Aufruf erlaubt.');
            }

            if (!($arguments['confirm'] ?? false)) {
                return ToolResult::error('CONFIRMATION_REQUIRED', 'Bulk-Löschung erfordert confirm: true. ' . count($ids) . ' Contact(s) würden gelöscht.');
            }

            $deleted = [];
            $errors = [];

            DB::transaction(function () use ($ids, $context, &$deleted, &$errors) {
                foreach ($ids as $index => $id) {
                    try {
                        if (!$id || !is_numeric($id)) {
                            $errors[] = ['index' => $index, 'id' => $id, 'error' => 'Ungültige Contact-ID.'];
                            continue;
                        }

                        $contact = CrmContact::find((int) $id);
                        if (!$contact) {
                            $errors[] = ['index' => $index, 'id' => $id, 'error' => 'Contact nicht gefunden.'];
                            continue;
                        }

                        try {
                            Gate::forUser($context->user)->authorize('delete', $contact);
                        } catch (AuthorizationException $e) {
                            $errors[] = ['index' => $index, 'id' => $id, 'error' => 'Keine Berechtigung zum Löschen.'];
                            continue;
                        }

                        $contactName = trim(($contact->first_name ?? '') . ' ' . ($contact->last_name ?? ''));
                        $contact->delete();

                        $deleted[] = [
                            'id' => (int) $id,
                            'name' => $contactName,
                        ];
                    } catch (\Throwable $e) {
                        $errors[] = ['index' => $index, 'id' => $id, 'error' => $e->getMessage()];
                    }
                }
            });

            return ToolResult::success([
                'deleted_count' => count($deleted),
                'error_count' => count($errors),
                'total_requested' => count($ids),
                'deleted' => $deleted,
                'errors' => $errors ?: null,
                'message' => count($deleted) . ' von ' . count($ids) . ' Contacts erfolgreich gelöscht.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Bulk-Löschen von Contacts: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['crm', 'contact', 'bulk', 'delete'],
            'risk_level' => 'destructive',
        ];
    }
}
