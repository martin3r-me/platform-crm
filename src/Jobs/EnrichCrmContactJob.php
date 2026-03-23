<?php

namespace Platform\Crm\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Models\CoreAiProvider;
use Platform\Core\Models\Team;
use Platform\Core\Models\User;
use Platform\Core\Services\AiToolLoopRunner;
use Platform\Crm\Models\CommsContactEnrichmentLog;
use Platform\Crm\Models\CommsEmailThread;
use Platform\Crm\Models\CommsWhatsAppMessage;
use Platform\Crm\Models\CommsWhatsAppThread;
use Platform\Crm\Models\CrmContact;

class EnrichCrmContactJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 120;

    public function __construct(
        public int $contactId,
        public string $threadType,
        public int $threadId,
        public string $source,
    ) {
        $this->onQueue('enrichment');
    }

    public function handle(): void
    {
        $lockKey = "crm:contact-enrichment:{$this->contactId}";
        $lock = Cache::lock($lockKey, 300);

        if (!$lock->get()) {
            Log::debug('[EnrichCrmContactJob] Lock aktiv, übersprungen', ['contact_id' => $this->contactId]);
            return;
        }

        try {
            $contact = CrmContact::find($this->contactId);
            if (!$contact) {
                return;
            }

            $team = Team::find($contact->team_id);
            if (!$team) {
                return;
            }

            $admin = $this->findTeamAdmin($team);
            if (!$admin) {
                $this->logEnrichment($contact, 'skipped', 'Kein Team-Admin gefunden');
                return;
            }

            $threadData = $this->loadThreadData();
            if (empty($threadData)) {
                $this->logEnrichment($contact, 'skipped', 'Keine Thread-Daten gefunden');
                return;
            }

            $this->logEnrichment($contact, 'run_started', 'Contact-Enrichment gestartet', [
                'thread_type' => $this->threadType,
                'thread_id' => $this->threadId,
                'source' => $this->source,
            ]);

            $this->impersonateForTask($admin, $team);

            $model = $this->determineModel();
            $runner = AiToolLoopRunner::make();

            $toolContext = new ToolContext($admin, $team, [
                'context_model' => CrmContact::class,
                'context_model_id' => $contact->id,
            ]);

            $preloadTools = [
                'crm.contacts.GET', 'crm.contacts.PUT',
                'crm.phone_numbers.POST',
                'crm.email_addresses.POST',
                'crm.postal_addresses.POST',
            ];

            $messages = $this->buildMessages($contact, $threadData);

            $result = $runner->run($messages, $model, $toolContext, [
                'max_iterations' => 10,
                'max_output_tokens' => 1000,
                'include_web_search' => false,
                'reasoning' => ['effort' => 'low'],
                'preload_tools' => $preloadTools,
                'skip_discovery_tools' => true,
                'on_tool_result' => function (string $tool, array $args, array $result) use ($contact) {
                    $ok = $result['ok'] ?? false;
                    $this->logEnrichment($contact, $ok ? 'tool_call' : 'tool_error', ($ok ? '' : 'FEHLER: ') . $tool, [
                        'tool' => $tool,
                        'args' => $args,
                        'ok' => $ok,
                        'result' => $ok ? ($result['data'] ?? null) : ($result['error'] ?? null),
                    ]);
                },
            ]);

            $iterations = (int) ($result['iterations'] ?? 0);
            $allToolCallNames = $result['all_tool_call_names'] ?? [];

            $this->logEnrichment($contact, 'run_completed', "Enrichment abgeschlossen: {$iterations} Iterationen", [
                'iterations' => $iterations,
                'all_tool_calls' => $allToolCallNames,
            ]);
        } catch (\Throwable $e) {
            Log::error('[EnrichCrmContactJob] Fehler', [
                'contact_id' => $this->contactId,
                'error' => $e->getMessage(),
            ]);

            if (isset($contact)) {
                $this->logEnrichment($contact, 'run_error', 'Enrichment-Fehler: ' . $e->getMessage());
            }
        } finally {
            $lock->release();
        }
    }

    private function loadThreadData(): array
    {
        if ($this->threadType === 'email') {
            $thread = CommsEmailThread::with(['inboundMails' => fn ($q) => $q->latest()->limit(3)])->find($this->threadId);
            if (!$thread) {
                return [];
            }

            $messages = [];
            foreach ($thread->inboundMails as $mail) {
                $messages[] = [
                    'from' => $mail->from,
                    'subject' => $mail->subject ?? $thread->subject,
                    'text_body' => mb_substr($mail->text_body ?? '', 0, 3000),
                    'received_at' => $mail->created_at?->toIso8601String(),
                ];
            }

            return [
                'type' => 'email',
                'thread_subject' => $thread->subject,
                'messages' => $messages,
            ];
        }

        if ($this->threadType === 'whatsapp') {
            $thread = CommsWhatsAppThread::find($this->threadId);
            if (!$thread) {
                return [];
            }

            $messages = CommsWhatsAppMessage::where('comms_whatsapp_thread_id', $thread->id)
                ->where('direction', 'inbound')
                ->latest()
                ->limit(5)
                ->get();

            $items = [];
            foreach ($messages as $msg) {
                $items[] = [
                    'text' => mb_substr($msg->body ?? '', 0, 2000),
                    'received_at' => $msg->created_at?->toIso8601String(),
                ];
            }

            return [
                'type' => 'whatsapp',
                'phone' => $thread->remote_phone_number,
                'messages' => $items,
            ];
        }

        return [];
    }

    private function buildMessages(CrmContact $contact, array $threadData): array
    {
        $system = "Du bist ein Datenextraktions-Agent für ein CRM-System.\n"
            . "Deine Aufgabe: Extrahiere Kontaktdaten aus den bereitgestellten Nachrichten und aktualisiere den CRM-Kontakt.\n\n"
            . "REGELN:\n"
            . "- Aktualisiere den CRM-Kontakt per crm.contacts.PUT (ID: {$contact->id}).\n"
            . "- Setze first_name, last_name, salutation_code (\"HERR\"/\"FRAU\"), gender_code (\"MALE\"/\"FEMALE\") wenn erkennbar.\n"
            . "- Setze birth_date (YYYY-MM-DD) wenn verfügbar.\n"
            . "- Lege Telefonnummern per crm.phone_numbers.POST an (entity_type=\"contact\", entity_id={$contact->id}).\n"
            . "- Lege E-Mail-Adressen per crm.email_addresses.POST an (entity_type=\"contact\", entity_id={$contact->id}, is_primary=true).\n"
            . "- Lege Postadressen per crm.postal_addresses.POST an (entity_type=\"contact\", entity_id={$contact->id}).\n"
            . "- Überschreibe nur Felder mit besseren/vollständigeren Daten.\n"
            . "- Wenn first_name 'Unbekannt' ist, versuche den echten Namen zu finden.\n\n"
            . "VERBOTEN:\n"
            . "- Sende KEINE Nachrichten.\n"
            . "- Rufe NICHT tools.GET auf.\n"
            . "- Beginne SOFORT mit Tool-Calls.\n";

        $contactData = [
            'contact_id' => $contact->id,
            'first_name' => $contact->first_name,
            'last_name' => $contact->last_name,
        ];

        $data = json_encode([
            'contact' => $contactData,
            'thread' => $threadData,
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $user = "Kontakt und Nachrichten (JSON):\n{$data}\n\n"
            . "Extrahiere alle Kontaktdaten und aktualisiere den CRM-Kontakt. Beginne SOFORT.";

        return [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $user],
        ];
    }

    private function findTeamAdmin(?Team $team): ?User
    {
        if (!$team) {
            return null;
        }

        return $team->users()->wherePivot('role', 'owner')->orderBy('id')->first()
            ?? $team->users()->wherePivot('role', 'admin')->orderBy('id')->first()
            ?? $team->users()->orderBy('id')->first();
    }

    private function impersonateForTask(User $user, ?Team $team): void
    {
        Auth::setUser($user);

        if ($team) {
            $user->current_team_id = (int) $team->id;
            $user->setRelation('currentTeamRelation', $team);
        }
    }

    private function determineModel(): string
    {
        try {
            $provider = CoreAiProvider::where('key', 'openai')->where('is_active', true)->with('defaultModel')->first();
            $fallback = $provider?->defaultModel?->model_id;
            if (is_string($fallback) && $fallback !== '') {
                return $fallback;
            }
        } catch (\Throwable $e) {}

        return 'gpt-4.1-mini';
    }

    private function logEnrichment(CrmContact $contact, string $type, string $summary, ?array $details = null): void
    {
        try {
            CommsContactEnrichmentLog::create([
                'crm_contact_id' => $contact->id,
                'type' => $type,
                'summary' => $summary,
                'details' => $details,
            ]);
        } catch (\Throwable $e) {
            // Logging should never break the run
        }
    }
}
