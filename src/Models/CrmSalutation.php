<?php

namespace Platform\Crm\Models;

use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Uid\UuidV7;

class CrmSalutation extends Model
{
    protected $table = 'crm_salutations';
    
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
     * Scope für aktive Anreden
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    /**
     * Scope für inaktive Anreden
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }
    
    /**
     * Standard-Anreden für Seeding
     */
    public static function getDefaultSalutations(): array
    {
        return [
            ['name' => 'Herr', 'code' => 'HERR'],
            ['name' => 'Frau', 'code' => 'FRAU'],
            ['name' => 'Divers', 'code' => 'DIVERS'],
            ['name' => 'Firma', 'code' => 'FIRMA'],
        ];
    }
} 