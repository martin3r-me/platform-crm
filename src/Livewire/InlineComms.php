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
