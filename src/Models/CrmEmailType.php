<?php

namespace Platform\Crm\Models;

use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Uid\UuidV7;

class CrmEmailType extends Model
{
    protected $table = 'crm_email_types';
    
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
     * Scope für aktive E-Mail-Typen
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    /**
     * Scope für inaktive E-Mail-Typen
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }
    
    /**
     * Standard-E-Mail-Typen für Seeding
     */
    public static function getDefaultEmailTypes(): array
    {
        return [
            ['name' => 'Privat', 'code' => 'PRIVATE'],
            ['name' => 'Geschäftlich', 'code' => 'BUSINESS'],
            ['name' => 'Newsletter', 'code' => 'NEWSLETTER'],
            ['name' => 'Support', 'code' => 'SUPPORT'],
            ['name' => 'Rechnung', 'code' => 'BILLING'],
            ['name' => 'Marketing', 'code' => 'MARKETING'],
            ['name' => 'Info', 'code' => 'INFO'],
            ['name' => 'Sonstige', 'code' => 'OTHER'],
        ];
    }
} 