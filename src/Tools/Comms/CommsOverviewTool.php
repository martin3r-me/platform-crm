<?php

namespace Platform\Crm\Tools\Comms;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;

/**
 * High-signal discovery entrypoint for Comms.
 *
 * Goal: make it obvious to an LLM how to send emails:
 * Connection -> Channel -> Thread -> Send -> Read timeline.
 */
class CommsOverviewTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'core.comms.overview.GET';
    }

    public function getDescription(): string
    {
        return 'GET /comms/overview – Übersicht über Communication-Tools und Workflows. Zeigt verfügbare Tools (channels, threads, messages) und typische Abläufe für E‑Mail und WhatsApp. REST-Parameter: keine.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => new \stdClass(),
            'required' => [],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        return ToolResult::success([
            'capabilities' => [
                'channels' => [
                    'purpose' => 'Sender-IDs (z.B. E‑Mail Absender) am Root-Team, inkl. Sichtbarkeit (private/team).',
                    'tools' => [
                        'list' => 'core.comms.channels.GET',
                        'create' => 'core.comms.channels.POST',
                        'update' => 'core.comms.channels.PUT',
                        'delete' => 'core.comms.channels.DELETE',
                    ],
                ],
                'email_threads' => [
                    'purpose' => 'Konversationen pro Kanal (thread token + subject + rollups).',
                    'tools' => [
                        'list' => 'core.comms.email_threads.GET',
                        'create' => 'core.comms.email_threads.POST',
                        'update' => 'core.comms.email_threads.PUT',
                        'delete' => 'core.comms.email_threads.DELETE',
                    ],
                ],
                'email_messages' => [
                    'purpose' => 'Timeline lesen + E‑Mails senden (Postmark).',
                    'tools' => [
                        'list' => 'core.comms.email_messages.GET',
                        'send' => 'core.comms.email_messages.POST',
                    ],
                ],
                'wa_overview' => [
                    'purpose' => 'WhatsApp-Dashboard: Ungelesene, aktive Threads, letzte Aktivitäten.',
                    'tools' => [
                        'overview' => 'core.comms.wa_overview.GET',
                    ],
                ],
                'wa_contacts' => [
                    'purpose' => 'WhatsApp-Kontakte (Konversationspartner) mit Telefonnummer, Name, letzter Aktivität.',
                    'tools' => [
                        'list' => 'core.comms.wa_contacts.GET',
                        'show' => 'core.comms.wa_contacts.SHOW',
                    ],
                ],
                'wa_threads' => [
                    'purpose' => 'Pseudo-Threads (Konversations-Threads) zum Organisieren von WhatsApp-Chats nach Thema.',
                    'tools' => [
                        'list' => 'core.comms.wa_threads.GET',
                        'create' => 'core.comms.wa_threads.POST',
                        'show' => 'core.comms.wa_threads.SHOW',
                    ],
                ],
                'wa_messages' => [
                    'purpose' => 'WhatsApp-Nachrichten lesen, senden, suchen.',
                    'tools' => [
                        'list' => 'core.comms.whatsapp_messages.GET',
                        'send' => 'core.comms.whatsapp_messages.POST',
                        'show' => 'core.comms.wa_messages.SHOW',
                        'search' => 'core.comms.wa_messages.search',
                    ],
                ],
            ],
            'typical_flows' => [
                [
                    'name' => 'E‑Mail senden (neuer Thread)',
                    'description' => 'Schritt-für-Schritt:',
                    'steps' => [
                        '1) core.comms.channels.GET { type: "email", provider: "postmark" } → wähle eine comms_channel_id aus dem Ergebnis',
                        '2) core.comms.email_messages.POST { comms_channel_id: <id>, to: "empfaenger@example.com", subject: "Betreff", body: "Nachrichtentext" }',
                        '3) Optional: core.comms.email_messages.GET { thread_id: <id> } um die Timeline zu sehen',
                    ],
                    'example' => [
                        'channels_get' => 'core.comms.channels.GET({ type: "email", provider: "postmark" })',
                        'send_post' => 'core.comms.email_messages.POST({ comms_channel_id: 1, to: "test@example.com", subject: "Test", body: "Hallo" })',
                    ],
                ],
                [
                    'name' => 'E‑Mail senden (Reply)',
                    'description' => 'Antwort auf bestehenden Thread:',
                    'steps' => [
                        '1) core.comms.email_threads.GET { comms_channel_id: <id> } → wähle eine thread_id aus',
                        '2) core.comms.email_messages.POST { thread_id: <id>, body: "Antworttext" }',
                        'Hinweis: to und subject werden automatisch aus dem Thread übernommen (keine Angabe nötig)',
                    ],
                    'example' => [
                        'threads_get' => 'core.comms.email_threads.GET({ comms_channel_id: 1 })',
                        'reply_post' => 'core.comms.email_messages.POST({ thread_id: 5, body: "Meine Antwort" })',
                    ],
                ],
                [
                    'name' => 'WhatsApp-Nachricht senden (neuer Kontakt)',
                    'description' => 'Schritt-für-Schritt:',
                    'steps' => [
                        '1) core.comms.channels.GET { type: "whatsapp" } → WhatsApp-Channel wählen',
                        '2) core.comms.whatsapp_messages.POST { comms_channel_id: <id>, to: "+491751234567", body: "Hallo!" }',
                        'Hinweis: Außerhalb des 24h-Fensters muss ein Template verwendet werden (template_name + template_params)',
                    ],
                ],
                [
                    'name' => 'WhatsApp-Nachricht senden (bestehender Kontakt)',
                    'description' => 'Antwort an bekannten Kontakt:',
                    'steps' => [
                        '1) core.comms.wa_contacts.GET → Kontakt finden',
                        '2) core.comms.whatsapp_messages.POST { thread_id: <id>, body: "Nachricht" }',
                    ],
                ],
                [
                    'name' => 'WhatsApp-Konversation nach Thema organisieren',
                    'description' => 'Pseudo-Threads für thematische Gruppierung:',
                    'steps' => [
                        '1) core.comms.wa_threads.POST { thread_id: <id>, label: "Neues Thema" } → Neuen Thread starten (schliesst aktiven)',
                        '2) Neue Nachrichten werden automatisch dem aktiven Thread zugeordnet',
                        '3) core.comms.wa_threads.GET { thread_id: <id> } → Alle Threads des Kontakts sehen',
                        '4) core.comms.wa_threads.SHOW { conversation_thread_id: <id> } → Thread mit Nachrichten laden',
                    ],
                ],
            ],
            'notes' => [
                'Comms-Daten sind root-scoped (Root-Team).',
                'Postmark Credentials liegen in der DB (Provider Connection).',
                'WhatsApp nutzt Meta Cloud API – Credentials in Provider Connection.',
                'WhatsApp 24h-Fenster: Freeform-Nachrichten nur innerhalb 24h nach letzter Inbound-Nachricht. Außerhalb: Templates nötig.',
                'Teamweite Kanäle/Threads löschen: nur Owner/Admin (Root-Team).',
            ],
        ]);
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'utility',
            'tags' => ['comms', 'overview', 'email', 'whatsapp', 'send', 'postmark'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => true,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}

