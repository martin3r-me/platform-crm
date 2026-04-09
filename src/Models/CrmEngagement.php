<?php

namespace Platform\Crm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\Uid\UuidV7;
use Platform\ActivityLog\Traits\LogsActivity;
use Platform\Crm\Traits\HasCompanyLinksTrait;
use Platform\Crm\Traits\HasContactLinksTrait;

class CrmEngagement extends Model
{
    use SoftDeletes, LogsActivity, HasCompanyLinksTrait, HasContactLinksTrait;

    protected $table = 'crm_engagements';

    const TYPE_NOTE = 'note';
    const TYPE_CALL = 'call';
    const TYPE_MEETING = 'meeting';
    const TYPE_TASK = 'task';

    protected $fillable = [
        'uuid',
        'type',
        'title',
        'body',
        'status',
        'priority',
        'scheduled_at',
        'ended_at',
        'completed_at',
        'metadata',
        'owned_by_user_id',
        'created_by_user_id',
        'team_id',
    ];

    protected $casts = [
        'metadata' => 'array',
        'scheduled_at' => 'datetime',
        'ended_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->uuid)) {
                do {
                    $uuid = UuidV7::generate();
                } while (self::where('uuid', $uuid)->exists());

                $model->uuid = $uuid;
            }
        });
    }

    // ─── Scopes ──────────────────────────────────────────────────

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeForTeam($query, int $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    public function scopeOpen($query)
    {
        return $query->whereNull('completed_at')
            ->where(function ($q) {
                $q->whereNull('status')
                    ->orWhereNotIn('status', ['completed', 'cancelled']);
            });
    }

    public function scopeCompleted($query)
    {
        return $query->where(function ($q) {
            $q->whereNotNull('completed_at')
                ->orWhere('status', 'completed');
        });
    }

    // ─── Relations ───────────────────────────────────────────────

    public function ownedByUser(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\User::class, 'owned_by_user_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\User::class, 'created_by_user_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\Team::class);
    }
}
