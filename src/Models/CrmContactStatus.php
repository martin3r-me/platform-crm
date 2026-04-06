<?php

namespace Platform\Crm\Models;

use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Uid\UuidV7;

class CrmContactStatus extends Model
{
    protected $table = 'crm_contact_statuses';
    
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
     * Scope für aktive Kontaktstatus
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    /**
     * Scope für inaktive Kontaktstatus
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }
    
    /**
     * Badge-Variant basierend auf dem Status-Code
     */
    public static function getVariantForCode(?string $code): string
    {
        return match ($code) {
            'CUSTOMER' => 'success',
            'ACTIVE' => 'success',
            'INTERESTED' => 'warning',
            'PARTNER' => 'info',
            'SUPPLIER' => 'info',
            'INACTIVE', 'FORMER_CUSTOMER', 'NOT_INTERESTED' => 'secondary',
            'BLOCKED', 'DELETED' => 'danger',
            'COMPETITOR' => 'warning',
            default => 'secondary',
        };
    }

    public function getVariant(): string
    {
        return static::getVariantForCode($this->code);
    }

    /**
     * Standard-Kontaktstatus für Seeding
     */
    public static function getDefaultContactStatuses(): array
    {
        return [
            ['name' => 'Aktiv', 'code' => 'ACTIVE'],
            ['name' => 'Inaktiv', 'code' => 'INACTIVE'],
            ['name' => 'Kunde', 'code' => 'CUSTOMER'],
            ['name' => 'Ehemaliger Kunde', 'code' => 'FORMER_CUSTOMER'],
            ['name' => 'Partner', 'code' => 'PARTNER'],
            ['name' => 'Lieferant', 'code' => 'SUPPLIER'],
            ['name' => 'Konkurrent', 'code' => 'COMPETITOR'],
            ['name' => 'Interessent', 'code' => 'INTERESTED'],
            ['name' => 'Nicht interessiert', 'code' => 'NOT_INTERESTED'],
            ['name' => 'Blockiert', 'code' => 'BLOCKED'],
            ['name' => 'Gelöscht', 'code' => 'DELETED'],
        ];
    }
} 