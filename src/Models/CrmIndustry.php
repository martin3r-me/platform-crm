<?php

namespace Platform\Crm\Models;

use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Uid\UuidV7;

class CrmIndustry extends Model
{
    protected $table = 'crm_industries';
    
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
     * Scope für aktive Branchen
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    /**
     * Scope für inaktive Branchen
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }
    
    /**
     * Standard-Branchen für Seeding
     */
    public static function getDefaultIndustries(): array
    {
        return [
            ['name' => 'Automobilindustrie', 'code' => 'AUTOMOTIVE'],
            ['name' => 'Banken & Finanzdienstleistungen', 'code' => 'BANKING'],
            ['name' => 'Bauwesen', 'code' => 'CONSTRUCTION'],
            ['name' => 'Chemie & Pharma', 'code' => 'CHEMICALS'],
            ['name' => 'Consulting', 'code' => 'CONSULTING'],
            ['name' => 'E-Commerce', 'code' => 'ECOMMERCE'],
            ['name' => 'Energie & Versorgung', 'code' => 'ENERGY'],
            ['name' => 'Forschung & Entwicklung', 'code' => 'RND'],
            ['name' => 'Gesundheitswesen', 'code' => 'HEALTHCARE'],
            ['name' => 'Handel & Einzelhandel', 'code' => 'RETAIL'],
            ['name' => 'Immobilien', 'code' => 'REAL_ESTATE'],
            ['name' => 'IT & Software', 'code' => 'IT_SOFTWARE'],
            ['name' => 'Logistik & Transport', 'code' => 'LOGISTICS'],
            ['name' => 'Luft- & Raumfahrt', 'code' => 'AEROSPACE'],
            ['name' => 'Maschinenbau', 'code' => 'MACHINERY'],
            ['name' => 'Medien & Unterhaltung', 'code' => 'MEDIA'],
            ['name' => 'Metallindustrie', 'code' => 'METALS'],
            ['name' => 'Nahrungsmittel & Getränke', 'code' => 'FOOD_BEVERAGE'],
            ['name' => 'Öffentlicher Sektor', 'code' => 'PUBLIC_SECTOR'],
            ['name' => 'Rechtswesen', 'code' => 'LEGAL'],
            ['name' => 'Telekommunikation', 'code' => 'TELECOM'],
            ['name' => 'Textil & Bekleidung', 'code' => 'TEXTILES'],
            ['name' => 'Tourismus & Gastronomie', 'code' => 'TOURISM'],
            ['name' => 'Versicherungen', 'code' => 'INSURANCE'],
            ['name' => 'Sonstige', 'code' => 'OTHER'],
        ];
    }
} 