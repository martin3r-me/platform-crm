<?php

namespace Platform\Crm\Models;

use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Uid\UuidV7;

class CrmPhoneType extends Model
{
    protected $table = 'crm_phone_types';
    
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
     * Scope für aktive Telefon-Typen
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    /**
     * Scope für inaktive Telefon-Typen
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }
    
    /**
     * Standard-Telefon-Typen für Seeding
     */
    public static function getDefaultPhoneTypes(): array
    {
        return [
            ['name' => 'Privat', 'code' => 'PRIVATE'],
            ['name' => 'Geschäftlich', 'code' => 'BUSINESS'],
            ['name' => 'Mobil', 'code' => 'MOBILE'],
            ['name' => 'Fax', 'code' => 'FAX'],
            ['name' => 'Notfall', 'code' => 'EMERGENCY'],
            ['name' => 'Hotline', 'code' => 'HOTLINE'],
            ['name' => 'Support', 'code' => 'SUPPORT'],
            ['name' => 'Sonstige', 'code' => 'OTHER'],
        ];
    }
} 