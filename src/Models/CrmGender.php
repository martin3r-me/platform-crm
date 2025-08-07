<?php

namespace Platform\Crm\Models;

use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Uid\UuidV7;

class CrmGender extends Model
{
    protected $table = 'crm_genders';
    
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
     * Scope für aktive Geschlechter
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    /**
     * Scope für inaktive Geschlechter
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }
    
    /**
     * Standard-Geschlechter für Seeding
     */
    public static function getDefaultGenders(): array
    {
        return [
            ['name' => 'Männlich', 'code' => 'MALE'],
            ['name' => 'Weiblich', 'code' => 'FEMALE'],
            ['name' => 'Divers', 'code' => 'DIVERSE'],
            ['name' => 'Nicht angegeben', 'code' => 'NOT_SPECIFIED'],
        ];
    }
} 