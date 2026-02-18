<?php

namespace Platform\Crm\Events;

use Platform\Crm\Models\CommsChannel;
use Platform\Crm\Models\CommsEmailThread;
use Platform\Crm\Models\CommsEmailInboundMail;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommsInboundReceived
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public CommsChannel $channel,
        public CommsEmailThread $thread,
        public CommsEmailInboundMail $mail,
        public bool $isNewThread,
    ) {}
}
