<?php

namespace Platform\Crm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Platform\ActivityLog\Traits\LogsActivity;
use Symfony\Component\Uid\UuidV7;

class CrmAccountPotential extends Model
{
    use LogsActivity;

    protected $table = 'crm_account_potentials';

    const CONFIDENCE_LOW = 'low';
    const CONFIDENCE_MEDIUM = 'medium';
    const CONFIDENCE_HIGH = 'high';
    const CONFIDENCE_VERY_HIGH = 'very_high';

    protected $fillable = [
        'uuid',
        'company_id',
        'year',
        'target_revenue',
        'additional_potential',
        'strategic_potential',
        'confidence',
        'notes',
        'created_by_user_id',
        'team_id',
    ];

    protected $casts = [
        'year' => 'integer',
        'target_revenue' => 'decimal:2',
        'additional_potential' => 'decimal:2',
        'strategic_potential' => 'decimal:2',
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

    public function company(): BelongsTo
    {
        return $this->belongsTo(CrmCompany::class, 'company_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\User::class, 'created_by_user_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\Team::class, 'team_id');
    }

    public function scopeForTeam($query, $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    public function scopeForYear($query, int $year)
    {
        return $query->where('year', $year);
    }

    public function getTotalPotentialAttribute(): float
    {
        return (float) ($this->target_revenue ?? 0)
             + (float) ($this->additional_potential ?? 0)
             + (float) ($this->strategic_potential ?? 0);
    }

    public function getConfidenceLabelAttribute(): ?string
    {
        return match ($this->confidence) {
            self::CONFIDENCE_LOW => 'Niedrig',
            self::CONFIDENCE_MEDIUM => 'Mittel',
            self::CONFIDENCE_HIGH => 'Hoch',
            self::CONFIDENCE_VERY_HIGH => 'Sehr hoch',
            default => null,
        };
    }

    public static function confidenceOptions(): array
    {
        return [
            ['value' => self::CONFIDENCE_LOW, 'label' => 'Niedrig'],
            ['value' => self::CONFIDENCE_MEDIUM, 'label' => 'Mittel'],
            ['value' => self::CONFIDENCE_HIGH, 'label' => 'Hoch'],
            ['value' => self::CONFIDENCE_VERY_HIGH, 'label' => 'Sehr hoch'],
        ];
    }
}
