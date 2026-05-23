<?php

namespace Platform\Crm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommsNewsletterAttachment extends Model
{
    protected $table = 'comms_newsletter_attachments';

    protected $fillable = [
        'newsletter_id',
        'filename',
        'mime',
        'size',
        'disk',
        'path',
    ];

    public function newsletter(): BelongsTo
    {
        return $this->belongsTo(CommsNewsletter::class, 'newsletter_id');
    }
}
