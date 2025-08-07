<?php

namespace Platform\Crm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Symfony\Component\Uid\UuidV7;

class CrmPhoneNumber extends Model
{
    protected $table = 'crm_phone_numbers';
    
    protected $fillable = [
        'uuid',
        'phoneable_type',
        'phoneable_id',
        'raw_input',
        'international',
        'national',
        'country_code',
        'extension',
        'notes',
        'phone_type_id',
        'is_primary',
        'is_active',
        'verified_at'
    ];
    
    protected $casts = [
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
        'verified_at' => 'datetime',
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
     * Polymorphe Beziehung zum Contact oder Company
     */
    public function phoneable(): MorphTo
    {
        return $this->morphTo();
    }
    
    /**
     * Beziehung zum Telefon-Typ
     */
    public function phoneType(): BelongsTo
    {
        return $this->belongsTo(CrmPhoneType::class, 'phone_type_id');
    }
    
    /**
     * Scope für aktive Telefonnummern
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    /**
     * Scope für inaktive Telefonnummern
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }
    
    /**
     * Scope für primäre Telefonnummern
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }
    
    /**
     * Scope für nicht-primäre Telefonnummern
     */
    public function scopeNotPrimary($query)
    {
        return $query->where('is_primary', false);
    }
    
    /**
     * Scope für bestimmten Telefon-Typ
     */
    public function scopeOfType($query, $typeId)
    {
        return $query->where('phone_type_id', $typeId);
    }
    
    /**
     * Scope für bestätigte Telefonnummern
     */
    public function scopeVerified($query)
    {
        return $query->whereNotNull('verified_at');
    }
    
    /**
     * Scope für nicht-bestätigte Telefonnummern
     */
    public function scopeUnverified($query)
    {
        return $query->whereNull('verified_at');
    }
    
    /**
     * Vollständige Telefonnummer mit Durchwahl
     */
    public function getFullPhoneNumberAttribute(): string
    {
        $number = $this->national ?: $this->international ?: $this->raw_input;
        
        if ($this->extension) {
            $number .= ' - ' . $this->extension;
        }
        
        return $number;
    }
    
    /**
     * Telefonnummer für Anrufe (international)
     */
    public function getCallableNumberAttribute(): string
    {
        return $this->international ?: $this->national ?: $this->raw_input;
    }
    
    /**
     * Telefonnummer für Anzeige (national)
     */
    public function getDisplayNumberAttribute(): string
    {
        return $this->national ?: $this->international ?: $this->raw_input;
    }
    
    /**
     * Telefonnummer bestätigen
     */
    public function markAsVerified(): void
    {
        $this->update(['verified_at' => now()]);
    }
    
    /**
     * Telefonnummer als unbestätigt markieren
     */
    public function markAsUnverified(): void
    {
        $this->update(['verified_at' => null]);
    }
} 