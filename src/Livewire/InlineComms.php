<?php

namespace Platform\Crm\Livewire;

use Livewire\Component;
use Platform\Crm\Livewire\Concerns\WithCommsChat;

/**
 * Inline communication component (Email + WhatsApp).
 *
 * Embeds directly on a page (like InlineTags/ExtraFields) instead of using a modal.
 * Receives context via mount() parameters rather than Livewire events.
 */
class InlineComms extends Component
{
    use WithCommsChat;

    /** Which channel type to show initially (email|whatsapp). Computed in mount(). */
    public string $initialChannel = 'email';

    public function mount(
        ?string $contextType = null,
        ?int $contextId = null,
        ?string $subject = null,
        array $recipients = [],
    ): void {
        $this->setCommsContext([
            'model' => $contextType,
            'modelId' => $contextId,
            'subject' => $subject,
            'recipients' => $recipients,
        ]);

        $this->loadEmailRuntime();
        $this->loadWhatsAppRuntime();

        // Build the flat context threads list for conversation-first UX
        $this->buildContextThreadsList();

        if (!empty($this->allContextThreads)) {
            // Threads exist → jump straight into conversation mode
            $this->switchToContextThread(0);

            // Set initialChannel based on the first thread's type
            $this->initialChannel = $this->allContextThreads[0]['type'] === 'whatsapp' ? 'whatsapp' : 'email';
        } else {
            // No threads → setup mode, auto-select channel type
            $emailContextThreads = collect($this->emailChannels)->sum('context_thread_count');
            $waContextThreads = collect($this->whatsappChannels)->sum('context_thread_count');

            if ($waContextThreads > 0 && $emailContextThreads === 0) {
                $this->initialChannel = 'whatsapp';
            } elseif ($emailContextThreads === 0 && $waContextThreads === 0) {
                if (empty($this->emailChannels) && !empty($this->whatsappChannels)) {
                    $this->initialChannel = 'whatsapp';
                }
            }
        }
    }

    protected function shouldRefreshTimelines(): bool
    {
        return true;
    }

    public function render()
    {
        return view('crm::livewire.inline-comms');
    }
}
