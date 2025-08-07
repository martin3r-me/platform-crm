<?php

namespace Platform\Crm\Models;

use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Uid\UuidV7;

class CrmAddressType extends Model
{
    protected $table = 'crm_address_types';
    
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
     * Scope für aktive Adresstypen
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    /**
     * Scope für inaktive Adresstypen
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }
    
    /**
     * Standard-Adresstypen für Seeding
     */
    public static function getDefaultAddressTypes(): array
    {
        return [
            ['name' => 'Privat', 'code' => 'PRIVATE'],
            ['name' => 'Geschäftlich', 'code' => 'BUSINESS'],
            ['name' => 'Rechnung', 'code' => 'BILLING'],
            ['name' => 'Lieferung', 'code' => 'SHIPPING'],
            ['name' => 'Hauptsitz', 'code' => 'HEADQUARTERS'],
            ['name' => 'Niederlassung', 'code' => 'BRANCH'],
            ['name' => 'Postfach', 'code' => 'PO_BOX'],
            ['name' => 'Sonstige', 'code' => 'OTHER'],
        ];
    }
} 