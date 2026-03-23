<?php

namespace Platform\Crm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommsContactEnrichmentLog extends Model
{
    public $timestamps = false;

    protected $table = 'comms_contact_enrichment_logs';

    protected $fillable = [
        'crm_contact_id', 'type', 'summary', 'details',
    ];

    protected $casts = [
        'details' => 'array',
        'created_at' => 'datetime',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(CrmContact::class, 'crm_contact_id');
    }
}
