<?php

namespace Platform\Crm\Models;

use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Uid\UuidV7;

class CrmContactRelationType extends Model
{
    protected $table = 'crm_contact_relation_types';
    
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
     * Scope für aktive Beziehungstypen
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    /**
     * Scope für inaktive Beziehungstypen
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }
    
    /**
     * Standard-Beziehungstypen für Seeding
     */
    public static function getDefaultRelationTypes(): array
    {
        return [
            ['name' => 'Angestellter', 'code' => 'EMPLOYEE'],
            ['name' => 'Geschäftsführer', 'code' => 'CEO'],
            ['name' => 'Geschäftsführer', 'code' => 'MANAGING_DIRECTOR'],
            ['name' => 'Prokurist', 'code' => 'PROCURIST'],
            ['name' => 'Abteilungsleiter', 'code' => 'DEPARTMENT_HEAD'],
            ['name' => 'Manager', 'code' => 'MANAGER'],
            ['name' => 'Mitarbeiter', 'code' => 'STAFF'],
            ['name' => 'Berater', 'code' => 'CONSULTANT'],
            ['name' => 'Freelancer', 'code' => 'FREELANCER'],
            ['name' => 'Lieferant', 'code' => 'SUPPLIER'],
            ['name' => 'Kunde', 'code' => 'CUSTOMER'],
            ['name' => 'Partner', 'code' => 'PARTNER'],
            ['name' => 'Investor', 'code' => 'INVESTOR'],
            ['name' => 'Aufsichtsrat', 'code' => 'SUPERVISORY_BOARD'],
            ['name' => 'Beirat', 'code' => 'ADVISORY_BOARD'],
            ['name' => 'Sonstige', 'code' => 'OTHER'],
        ];
    }
} 