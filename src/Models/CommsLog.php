<?php

namespace Platform\Crm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Platform\Core\Models\User;

class CommsLog extends Model
{
    public $timestamps = false;

    protected $table = 'comms_logs';

    protected $fillable = [
        'team_id',
        'channel_type',
        'channel_id',
        'event',
        'status',
        'summary',
        'details',
        'recipient',
        'thread_id',
        'message_id',
        'triggered_by_user_id',
        'source',
    ];

    protected $casts = [
        'details' => 'array',
        'created_at' => 'datetime',
    ];

    public function channel(): BelongsTo
    {
        return $this->belongsTo(CommsChannel::class, 'channel_id');
    }

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by_user_id');
    }

    public function thread(): BelongsTo
    {
        return $this->belongsTo(CommsWhatsAppThread::class, 'thread_id');
    }

    /**
     * Create a comms log entry.
     */
    public static function log(string $event, string $status, string $summary, array $details = [], array $extra = []): self
    {
        return static::create(array_merge([
            'event' => $event,
            'status' => $status,
            'summary' => $summary,
            'details' => $details ?: null,
        ], $extra));
    }
}
