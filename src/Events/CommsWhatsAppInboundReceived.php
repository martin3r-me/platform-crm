<?php

namespace Platform\Crm\Events;

use Platform\Crm\Models\CommsChannel;
use Platform\Crm\Models\CommsWhatsAppThread;
use Platform\Crm\Models\CommsWhatsAppMessage;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommsWhatsAppInboundReceived
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public CommsChannel $channel,
        public CommsWhatsAppThread $thread,
        public CommsWhatsAppMessage $message,
        public bool $isNewThread,
    ) {}
}
