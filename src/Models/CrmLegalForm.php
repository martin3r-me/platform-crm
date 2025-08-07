<?php

namespace Platform\Crm\Models;

use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Uid\UuidV7;

class CrmLegalForm extends Model
{
    protected $table = 'crm_legal_forms';
    
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
     * Scope für aktive Rechtsformen
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    /**
     * Scope für inaktive Rechtsformen
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }
    
    /**
     * Standard-Rechtsformen für Seeding
     */
    public static function getDefaultLegalForms(): array
    {
        return [
            // Deutsche Rechtsformen
            ['name' => 'GmbH', 'code' => 'GMBH'],
            ['name' => 'AG', 'code' => 'AG'],
            ['name' => 'UG', 'code' => 'UG'],
            ['name' => 'OHG', 'code' => 'OHG'],
            ['name' => 'KG', 'code' => 'KG'],
            ['name' => 'GbR', 'code' => 'GBR'],
            ['name' => 'e.K.', 'code' => 'EK'],
            ['name' => 'e.V.', 'code' => 'EV'],
            ['name' => 'Einzelunternehmen', 'code' => 'EINZELUNTERNEHMEN'],
            ['name' => 'Stiftung', 'code' => 'STIFTUNG'],
            
            // Internationale Rechtsformen
            ['name' => 'LLC', 'code' => 'LLC'],
            ['name' => 'Ltd.', 'code' => 'LTD'],
            ['name' => 'S.A.', 'code' => 'SA'],
            ['name' => 'S.p.A.', 'code' => 'SPA'],
            ['name' => 'S.L.', 'code' => 'SL'],
            ['name' => 'B.V.', 'code' => 'BV'],
            ['name' => 'A/S', 'code' => 'AS'],
            ['name' => 'S.A.S.', 'code' => 'SAS'],
            
            // Sonstige
            ['name' => 'Behörde', 'code' => 'BEHOERDE'],
            ['name' => 'NGO', 'code' => 'NGO'],
            ['name' => 'Tochtergesellschaft', 'code' => 'TOCHTERGESELLSCHAFT'],
            ['name' => 'Filiale', 'code' => 'FILIALE'],
            ['name' => 'Zweigniederlassung', 'code' => 'ZWIGNIEDERLASSUNG'],
            ['name' => 'Sonstige', 'code' => 'SONSTIGE'],
        ];
    }
} 