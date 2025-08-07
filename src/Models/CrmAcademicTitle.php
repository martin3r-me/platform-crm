<?php

namespace Platform\Crm\Models;

use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Uid\UuidV7;

class CrmAcademicTitle extends Model
{
    protected $table = 'crm_academic_titles';
    
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
     * Scope für aktive akademische Titel
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    /**
     * Scope für inaktive akademische Titel
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }
    
    /**
     * Standard-akademische Titel für Seeding
     */
    public static function getDefaultTitles(): array
    {
        return [
            ['name' => 'Dr.', 'code' => 'DR'],
            ['name' => 'Prof.', 'code' => 'PROF'],
            ['name' => 'Prof. Dr.', 'code' => 'PROF_DR'],
            ['name' => 'Dr. med.', 'code' => 'DR_MED'],
            ['name' => 'Dr. rer. nat.', 'code' => 'DR_RER_NAT'],
            ['name' => 'Dr. phil.', 'code' => 'DR_PHIL'],
            ['name' => 'Dr. jur.', 'code' => 'DR_JUR'],
            ['name' => 'Dr. ing.', 'code' => 'DR_ING'],
            ['name' => 'Dipl.-Ing.', 'code' => 'DIPL_ING'],
            ['name' => 'Dipl.-Kfm.', 'code' => 'DIPL_KFM'],
            ['name' => 'M.Sc.', 'code' => 'MSC'],
            ['name' => 'B.Sc.', 'code' => 'BSC'],
            ['name' => 'MBA', 'code' => 'MBA'],
        ];
    }
} 