<?php

namespace Platform\Crm\Services\Comms;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Platform\Crm\Models\CommsChannel;
use Platform\Crm\Models\CommsEmailInboundMail;
use Platform\Crm\Models\CommsEmailOutboundMail;
use Platform\Crm\Models\CommsEmailThread;
use Platform\Crm\Models\CommsProviderConnection;
use Platform\Core\Models\User;
use Postmark\PostmarkClient;
use Postmark\Models\PostmarkAttachment;

class PostmarkEmailService
{
    public function send(
        CommsChannel $channel,
        string $to,
        string $subject,
        string $htmlBody,
        ?string $textBody = null,
        array $files = [],
        array $opt = [],
    ): string {
        if ($channel->type !== 'email' || $channel->provider !== 'postmark') {
            throw new \InvalidArgumentException('Channel must be type=email and provider=postmark.');
        }

        $channel->loadMissing('providerConnection');
        $connection = $channel->providerConnection ?: CommsProviderConnection::query()->find($channel->comms_provider_connection_id);
        $creds = is_array($connection?->credentials) ? $connection->credentials : [];
        $serverToken = (string) ($creds['server_token'] ?? '');
        if ($serverToken === '') {
            throw new \RuntimeException('Missing Postmark server_token in provider connection.');
        }

        $client = new PostmarkClient($serverToken);

        // 1) Thread & Token
        $token = $opt['token'] ?? Str::ulid()->toBase32();

        $thread = CommsEmailThread::query()->firstOrCreate(
            [
                'comms_channel_id' => $channel->id,
                'token' => $token,
            ],
            [
                'team_id' => $channel->team_id,
                'subject' => $subject,
            ]
        );

        // 2) Ticket number prefix (e.g. [#1042]) for helpdesk context
        $contextModel = $opt['context_model'] ?? null;
        $contextModelId = $opt['context_model_id'] ?? null;
        if ($contextModel && $contextModelId && $this->isHelpdeskTicket($contextModel)) {
            if (!preg_match('/\[#\d+\]/', $subject)) {
                $subject = "[#{$contextModelId}] {$subject}";
                // Update thread subject on first send
                if (!$thread->subject || !preg_match('/\[#\d+\]/', $thread->subject)) {
                    $thread->updateQuietly(['subject' => $subject]);
                }
            }
        }

        // 3) Re: prefix for replies
        if (($opt['is_reply'] ?? false) && !preg_match('/^Re:/i', $subject)) {
            $subject = 'Re: ' . $subject;
        }

        // 3) Marker & (optional) signature
        $signatureHtml = '';
        $signatureText = '';
        if (($opt['sender'] ?? null) instanceof User) {
            $sigName = $opt['sender']->fullname
                ?? trim(($opt['sender']->first_name ?? '') . ' ' . ($opt['sender']->last_name ?? ''))
                ?: null;
            if ($sigName) {
                $signatureHtml = "<br><br><p style=\"font-size: 13px; color: #444; margin: 0;\">&ndash;&ndash;<br>{$sigName}</p>";
                $signatureText = "\n\n--\n{$sigName}";
            }
        }

        $htmlBody .= $signatureHtml;

        $textBody ??= strip_tags($htmlBody);
        $textBody .= $signatureText;

        // 3b) Ticket context banner + reply hint
        if ($thread && $contextModel && $contextModelId && $this->isHelpdeskTicket($contextModel)) {
            $ticketModel = \Platform\Helpdesk\Models\HelpdeskTicket::find($contextModelId);
            $inboundCount = CommsEmailInboundMail::where('thread_id', $thread->id)->count();
            $outboundCount = CommsEmailOutboundMail::where('thread_id', $thread->id)->count();
            $messageCount = $inboundCount + $outboundCount;
            $createdAt = $ticketModel?->created_at?->format('d.m.Y H:i');

            // Build context lines
            $contextParts = [];
            $contextParts[] = "Ticket [#{$contextModelId}]";
            if ($createdAt) {
                $contextParts[] = "Erstellt: {$createdAt}";
            }
            if ($messageCount > 0) {
                $contextParts[] = "{$messageCount} " . ($messageCount === 1 ? 'Nachricht' : 'Nachrichten');
            }
            if ($ticketModel?->title) {
                $contextParts[] = "Betreff: {$ticketModel->title}";
            }

            $replyHint = 'Bitte antworten Sie direkt auf diese E-Mail, um auf das Ticket zu reagieren.';

            // HTML context footer
            $htmlBody .= '<br>'
                . '<div style="margin-top:16px;padding:10px 14px;background:#f8f9fa;border:1px solid #e0e0e0;border-radius:6px;font-size:12px;color:#666;line-height:1.6;">'
                . '<div>' . e(implode(' · ', $contextParts)) . '</div>'
                . '<div style="margin-top:4px;font-style:italic;">' . e($replyHint) . '</div>'
                . '</div>';

            // Plain text context footer
            $contextLine = implode(' | ', $contextParts);
            $textBody .= "\n\n---\n{$contextLine}\n{$replyHint}\n---";
        }

        // 3c) Quoted reply: append previous message for context
        if (($opt['is_reply'] ?? false) && $thread) {
            $lastInbound = CommsEmailInboundMail::query()
                ->where('thread_id', $thread->id)
                ->orderByDesc('received_at')
                ->first();

            if ($lastInbound) {
                $quoteFrom = $lastInbound->from ?: 'Unbekannt';
                $quoteDate = $lastInbound->received_at?->format('d.m.Y H:i') ?: $lastInbound->created_at?->format('d.m.Y H:i') ?: '';
                $quoteHeader = "Am {$quoteDate} schrieb {$quoteFrom}:";

                // HTML quoted reply
                $quotedBody = $lastInbound->html_body ?: nl2br(e($lastInbound->text_body ?? ''));
                $htmlBody .= '<br><br>'
                    . '<div style="margin-top:16px;padding-top:12px;border-top:1px solid #e0e0e0;">'
                    . '<p style="font-size:12px;color:#666;margin:0 0 8px 0;">' . e($quoteHeader) . '</p>'
                    . '<blockquote style="margin:0;padding-left:12px;border-left:3px solid #ccc;color:#666;">'
                    . $quotedBody
                    . '</blockquote></div>';

                // Plain text quoted reply
                $quoteText = $lastInbound->text_body ?: strip_tags($lastInbound->html_body ?? '');
                $quotedLines = collect(explode("\n", $quoteText))->map(fn ($line) => '> ' . $line)->implode("\n");
                $textBody .= "\n\n{$quoteHeader}\n{$quotedLines}";
            }
        }

        // 4) Attachments
        // Supports three input formats per entry:
        //  a) UploadedFile  -> stored on the 'emails' disk under threads/{thread}/{name}
        //  b) string path   -> absolute filesystem path (legacy fallback)
        //  c) array         -> ['disk' => 'emails', 'path' => 'threads/X/file.pdf', 'name' => '...', 'mime' => '...']
        //                       used to re-attach existing stored files (e.g. when forwarding)
        $pmAttachments = [];
        $storedAttachments = [];

        foreach ($files as $file) {
            // Case c) Existing stored file (e.g. forwarded attachment)
            if (is_array($file)) {
                $disk = (string) ($file['disk'] ?? 'emails');
                $relPath = (string) ($file['path'] ?? '');
                if ($relPath === '' || !Storage::disk($disk)->exists($relPath)) {
                    continue;
                }

                $name = (string) ($file['name'] ?? basename($relPath));
                $mime = (string) ($file['mime'] ?? Storage::disk($disk)->mimeType($relPath) ?: 'application/octet-stream');
                $absPath = Storage::disk($disk)->path($relPath);
                if (!is_file($absPath) || filesize($absPath) === 0) {
                    continue;
                }

                $pmAttachments[] = PostmarkAttachment::fromFile($absPath, $name, $mime);
                $storedAttachments[] = [
                    'name' => $name,
                    'mime' => $mime,
                    'storedPath' => $relPath,
                ];
                continue;
            }

            $path = $file instanceof UploadedFile ? $file->getRealPath() : $file;
            if (!is_string($path) || !is_file($path) || filesize($path) === 0) {
                continue;
            }

            $name = $file instanceof UploadedFile ? $file->getClientOriginalName() : basename($path);
            $mime = $file instanceof UploadedFile ? $file->getClientMimeType() : mime_content_type($path);

            $pmAttachments[] = PostmarkAttachment::fromFile($path, $name, $mime ?: 'application/octet-stream');

            if ($file instanceof UploadedFile) {
                Storage::disk('emails')->putFileAs("threads/{$thread->id}", $file, $name);
                $storedPath = "threads/{$thread->id}/{$name}";
            } else {
                $storedPath = $path;
            }

            $storedAttachments[] = [
                'name' => $name,
                'mime' => $mime,
                'storedPath' => $storedPath,
            ];
        }

        $pmAttachments = $pmAttachments ?: null;

        // 5) Send via Postmark (always send conversation token header)
        // NOTE: postmark-php expects an associative array; it converts to [{Name,Value}, ...] internally.
        // Build RFC 5322 compliant headers for best deliverability
        $senderEmail = $this->extractEmailAddress($channel->sender_identifier) ?: $channel->sender_identifier;
        $messageIdDomain = substr(strrchr($senderEmail, '@'), 1) ?: 'postmark';
        
        // Generate RFC 5322 compliant Message-ID: <unique-id@domain>
        // Format: timestamp + ULID for uniqueness, using sender domain for better alignment
        $messageId = '<' . time() . '.' . Str::ulid()->toBase32() . '@' . $messageIdDomain . '>';
        
        // Best practice headers for deliverability (especially Microsoft 365/Outlook)
        // - Message-ID: Required for threading and spam filtering (RFC 5322)
        // - MIME-Version: Required when using MIME features (multipart, HTML, attachments)
        // - Date: Postmark sets this automatically, but explicit is better
        // Note: Content-Type is automatically set by Postmark based on HtmlBody/TextBody/Attachments
        $headersArray = [
            'X-Conversation-Token' => $token,
            'Message-ID' => $messageId,
            'MIME-Version' => '1.0',
        ];

        $fromName = null;
        if (($opt['sender'] ?? null) instanceof User) {
            $fromName = $opt['sender']->fullname
                ?? trim(($opt['sender']->first_name ?? '') . ' ' . ($opt['sender']->last_name ?? ''))
                ?: null;
        }
        $fromName ??= ($channel->name ?: null);
        $from = $fromName ? "{$fromName} <{$channel->sender_identifier}>" : $channel->sender_identifier;

        $client->sendEmail(
            $from,
            $to,
            $subject,
            $htmlBody,
            $textBody,
            $opt['tag'] ?? null,
            $opt['track_opens'] ?? true,
            $opt['reply_to'] ?? null,
            $opt['cc'] ?? null,
            $opt['bcc'] ?? null,
            $headersArray,
            $pmAttachments,
            $opt['track_links'] ?? null,
            $opt['metadata'] ?? null,
            null // message stream (optional)
        );

        // 6) Persist outbound mail
        $mail = CommsEmailOutboundMail::create([
            'thread_id' => $thread->id,
            'comms_channel_id' => $channel->id,
            'created_by_user_id' => (($opt['sender'] ?? null) instanceof User) ? $opt['sender']->id : null,
            'from' => $from,
            'to' => $to,
            'cc' => $opt['cc'] ?? null,
            'bcc' => $opt['bcc'] ?? null,
            'reply_to' => $opt['reply_to'] ?? null,
            'subject' => $subject,
            'html_body' => $htmlBody,
            'text_body' => $textBody,
            'meta' => [
                'token' => $token,
            ],
            'sent_at' => now(),
        ]);
        $thread->touch();

        // Thread rollups
        $thread->last_outbound_to = $to;
        $thread->last_outbound_to_address = $this->extractEmailAddress($to) ?: $to;
        $thread->last_outbound_at = $mail->sent_at ?? now();
        if (!$thread->subject) {
            $thread->subject = $subject;
        }
        $thread->save();

        foreach ($storedAttachments as $a) {
            $mail->attachments()->create([
                'filename' => $a['name'],
                'mime' => $a['mime'],
                'size' => Storage::disk('emails')->exists($a['storedPath'])
                    ? Storage::disk('emails')->size($a['storedPath'])
                    : null,
                'disk' => 'emails',
                'path' => $a['storedPath'],
                'inline' => false,
            ]);
        }

        return $token;
    }

    private function isHelpdeskTicket(?string $contextModel): bool
    {
        if (!$contextModel) {
            return false;
        }

        $variants = [
            'Platform\\Helpdesk\\Models\\HelpdeskTicket',
            'HelpdeskTicket',
            'helpdesk_ticket',
        ];

        return in_array($contextModel, $variants, true);
    }

    private function extractEmailAddress(string $raw): ?string
    {
        if (preg_match('/<([^>]+)>/', $raw, $m)) {
            return trim((string) ($m[1] ?? '')) ?: null;
        }
        if (filter_var($raw, FILTER_VALIDATE_EMAIL)) {
            return $raw;
        }
        return null;
    }
}

