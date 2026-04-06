<?php

namespace Platform\Crm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Symfony\Component\Uid\UuidV7;

class CrmFollowUp extends Model
{
    protected $table = 'crm_follow_ups';

    protected $fillable = [
        'uuid',
        'followupable_type',
        'followupable_id',
        'title',
        'due_date',
        'completed_at',
        'created_by_user_id',
        'team_id',
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->uuid)) {
                $model->uuid = UuidV7::generate();
            }
        });
    }

    public function followupable(): MorphTo
    {
        return $this->morphTo();
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\User::class, 'created_by_user_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\Team::class, 'team_id');
    }

    // Scopes

    public function scopeOpen($query)
    {
        return $query->whereNull('completed_at');
    }

    public function scopeCompleted($query)
    {
        return $query->whereNotNull('completed_at');
    }

    public function scopeOverdue($query)
    {
        return $query->whereNull('completed_at')->where('due_date', '<', now()->toDateString());
    }

    public function scopeDueToday($query)
    {
        return $query->whereNull('completed_at')->whereDate('due_date', now()->toDateString());
    }

    public function scopeUpcoming($query, int $days = 7)
    {
        return $query->whereNull('completed_at')
            ->where('due_date', '>=', now()->toDateString())
            ->where('due_date', '<=', now()->addDays($days)->toDateString());
    }

    public function scopeForTeam($query, int $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    // Helpers

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    public function isOverdue(): bool
    {
        return !$this->isCompleted() && $this->due_date->lt(now()->startOfDay());
    }

    public function isDueToday(): bool
    {
        return !$this->isCompleted() && $this->due_date->isToday();
    }
}
