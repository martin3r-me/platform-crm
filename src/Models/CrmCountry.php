<?php

namespace Platform\Crm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Symfony\Component\Uid\UuidV7;

class CrmCountry extends Model
{
    protected $table = 'crm_countries';
    
    protected $fillable = [
        'uuid',
        'name',
        'code',
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
     * Scope für aktive Länder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    /**
     * Scope für inaktive Länder
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }
    
    /**
     * Beziehung zu Bundesländern
     */
    public function states(): HasMany
    {
        return $this->hasMany(CrmState::class, 'country_id');
    }
    
    /**
     * Beziehung zu aktiven Bundesländern
     */
    public function activeStates(): HasMany
    {
        return $this->hasMany(CrmState::class, 'country_id')->where('is_active', true);
    }
} 