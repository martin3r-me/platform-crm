<?php

namespace Platform\Crm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommsNewsletterRecipient extends Model
{
    protected $table = 'comms_newsletter_recipients';

    protected $fillable = [
        'newsletter_id',
        'contact_id',
        'email_address',
        'status',
        'sent_at',
        'opened_at',
        'clicked_at',
        'bounced_at',
        'error_message',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'opened_at' => 'datetime',
        'clicked_at' => 'datetime',
        'bounced_at' => 'datetime',
    ];

    public function newsletter(): BelongsTo
    {
        return $this->belongsTo(CommsNewsletter::class, 'newsletter_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(CrmContact::class, 'contact_id');
    }
}
