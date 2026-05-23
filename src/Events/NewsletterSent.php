<?php

namespace Platform\Crm\Events;

use Platform\Crm\Models\CommsNewsletter;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewsletterSent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public CommsNewsletter $newsletter,
    ) {}
}
