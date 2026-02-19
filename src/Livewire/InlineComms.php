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

        // Auto-select the channel type that has context threads
        $emailContextThreads = collect($this->emailChannels)->sum('context_thread_count');
        $waContextThreads = collect($this->whatsappChannels)->sum('context_thread_count');

        if ($waContextThreads > 0 && $emailContextThreads === 0) {
            $this->initialChannel = 'whatsapp';
        } elseif ($emailContextThreads === 0 && $waContextThreads === 0) {
            // No threads at all – prefer whichever has channels; fallback email
            if (empty($this->emailChannels) && !empty($this->whatsappChannels)) {
                $this->initialChannel = 'whatsapp';
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
