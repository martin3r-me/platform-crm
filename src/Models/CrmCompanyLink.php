<?php

namespace Platform\Crm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Symfony\Component\Uid\UuidV7;

class CrmCompanyLink extends Model
{
    protected $table = 'crm_company_links';
    
    protected $fillable = [
        'uuid',
        'company_id',
        'linkable_id',
        'linkable_type',
        'team_id',
        'created_by_user_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
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

            // Team-Kontext setzen
            if (empty($model->team_id) && auth()->check()) {
                $model->team_id = auth()->user()->current_team_id;
            }

            // Created by setzen
            if (empty($model->created_by_user_id) && auth()->check()) {
                $model->created_by_user_id = auth()->id();
            }
        });
    }
    
    /**
     * Scope für aktuelles Team
     */
    public function scopeForCurrentTeam($query)
    {
        return $query->where('team_id', auth()->user()->current_team_id);
    }

    /**
     * Scope für sichtbare Links (dynamische Prüfung über Company)
     */
    public function scopeVisible($query)
    {
        return $query->with('company')->get()->filter(function ($link) {
            return $link->company && $link->company->is_active;
        });
    }

    /**
     * Zugehörige Company
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(CrmCompany::class, 'company_id');
    }

    /**
     * Verlinkte Entität (Employer, etc.)
     */
    public function linkable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Erstellt von User
     */
    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\User::class, 'created_by_user_id');
    }

    /**
     * Team
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\Team::class, 'team_id');
    }
    
    /**
     * Scopes
     */
    public function scopeForTeam($query, $teamId)
    {
        return $query->where('team_id', $teamId);
    }
    
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }
    
    public function scopeOfType($query, $linkableType)
    {
        return $query->where('linkable_type', $linkableType);
    }
}
