<?php

namespace Platform\Crm\Models;

use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Uid\UuidV7;

class CrmLanguage extends Model
{
    protected $table = 'crm_languages';
    
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
     * Scope für aktive Sprachen
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    /**
     * Scope für inaktive Sprachen
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }
    
    /**
     * Standard-Sprachen für Seeding (ISO 639-1)
     */
    public static function getDefaultLanguages(): array
    {
        return [
            ['name' => 'Deutsch', 'code' => 'de'],
            ['name' => 'English', 'code' => 'en'],
            ['name' => 'Français', 'code' => 'fr'],
            ['name' => 'Español', 'code' => 'es'],
            ['name' => 'Italiano', 'code' => 'it'],
            ['name' => 'Português', 'code' => 'pt'],
            ['name' => 'Nederlands', 'code' => 'nl'],
            ['name' => 'Polski', 'code' => 'pl'],
            ['name' => 'Čeština', 'code' => 'cs'],
            ['name' => 'Slovenčina', 'code' => 'sk'],
            ['name' => 'Magyar', 'code' => 'hu'],
            ['name' => 'Română', 'code' => 'ro'],
            ['name' => 'Български', 'code' => 'bg'],
            ['name' => 'Hrvatski', 'code' => 'hr'],
            ['name' => 'Slovenščina', 'code' => 'sl'],
            ['name' => 'Eesti', 'code' => 'et'],
            ['name' => 'Latviešu', 'code' => 'lv'],
            ['name' => 'Lietuvių', 'code' => 'lt'],
            ['name' => 'Suomi', 'code' => 'fi'],
            ['name' => 'Svenska', 'code' => 'sv'],
            ['name' => 'Dansk', 'code' => 'da'],
            ['name' => 'Norsk', 'code' => 'no'],
            ['name' => 'Íslenska', 'code' => 'is'],
            ['name' => 'Русский', 'code' => 'ru'],
            ['name' => 'Українська', 'code' => 'uk'],
            ['name' => 'Türkçe', 'code' => 'tr'],
            ['name' => 'العربية', 'code' => 'ar'],
            ['name' => '中文', 'code' => 'zh'],
            ['name' => '日本語', 'code' => 'ja'],
            ['name' => '한국어', 'code' => 'ko'],
        ];
    }
} 