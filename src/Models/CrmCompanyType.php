<?php

namespace Platform\Crm\Models;

use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Uid\UuidV7;

class CrmCompanyType extends Model
{
    protected $table = 'crm_company_types';

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
     * Scope für aktive Company-Typen
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope für inaktive Company-Typen
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Badge-Variant basierend auf dem Typ-Code
     */
    public static function getVariantForCode(?string $code): string
    {
        return match ($code) {
            'PARTNER' => 'info',
            'WHOLESALER' => 'success',
            'END_CUSTOMER' => 'success',
            default => 'secondary',
        };
    }

    public function getVariant(): string
    {
        return static::getVariantForCode($this->code);
    }

    /**
     * Standard-Company-Typen für Seeding.
     *
     * Partner  = Organisation, die wir im Markt vertreten.
     * Abnehmer = Organisation, die Produkte bezieht; Untertypen
     *            Großhändler und Endkunde.
     */
    public static function getDefaultCompanyTypes(): array
    {
        return [
            ['name' => 'Partner', 'code' => 'PARTNER'],
            ['name' => 'Großhändler', 'code' => 'WHOLESALER'],
            ['name' => 'Endkunde', 'code' => 'END_CUSTOMER'],
        ];
    }
}
