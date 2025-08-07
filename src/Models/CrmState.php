<?php

namespace Platform\Crm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\Uid\UuidV7;

class CrmState extends Model
{
    protected $table = 'crm_states';
    
    protected $fillable = [
        'uuid',
        'name',
        'code',
        'country_id',
        'is_active'
    ];
    
    protected $casts = [
        'is_active' => 'boolean',
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
    
    /**
     * Beziehung zum Land
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(CrmCountry::class, 'country_id');
    }
    
    /**
     * Scope für aktive Bundesländer
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    /**
     * Scope für inaktive Bundesländer
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }
    
    /**
     * Scope für Bundesländer eines bestimmten Landes
     */
    public function scopeForCountry($query, $countryId)
    {
        return $query->where('country_id', $countryId);
    }
    
    /**
     * Scope für aktive Bundesländer eines bestimmten Landes
     */
    public function scopeActiveForCountry($query, $countryId)
    {
        return $query->where('country_id', $countryId)->where('is_active', true);
    }
} 