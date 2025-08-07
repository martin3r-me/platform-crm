<?php

namespace Platform\Crm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Symfony\Component\Uid\UuidV7;

class CrmPostalAddress extends Model
{
    protected $table = 'crm_postal_addresses';
    
    protected $fillable = [
        'uuid',
        'addressable_type',
        'addressable_id',
        'street',
        'house_number',
        'postal_code',
        'city',
        'additional_info',
        'country_id',
        'state_id',
        'address_type_id',
        'is_primary',
        'is_active'
    ];
    
    protected $casts = [
        'is_primary' => 'boolean',
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
     * Polymorphe Beziehung zum Contact oder Company
     */
    public function addressable(): MorphTo
    {
        return $this->morphTo();
    }
    
    /**
     * Beziehung zum Land
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(CrmCountry::class, 'country_id');
    }
    
    /**
     * Beziehung zum Bundesland
     */
    public function state(): BelongsTo
    {
        return $this->belongsTo(CrmState::class, 'state_id');
    }
    
    /**
     * Beziehung zum Adresstyp
     */
    public function addressType(): BelongsTo
    {
        return $this->belongsTo(CrmAddressType::class, 'address_type_id');
    }
    
    /**
     * Scope für aktive Adressen
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    /**
     * Scope für inaktive Adressen
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }
    
    /**
     * Scope für primäre Adressen
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }
    
    /**
     * Scope für nicht-primäre Adressen
     */
    public function scopeNotPrimary($query)
    {
        return $query->where('is_primary', false);
    }
    
    /**
     * Vollständige Adresse als String
     */
    public function getFullAddressAttribute(): string
    {
        $parts = [];
        
        if ($this->street) {
            $parts[] = $this->street;
            if ($this->house_number) {
                $parts[] = $this->house_number;
            }
        }
        
        if ($this->postal_code || $this->city) {
            $parts[] = trim($this->postal_code . ' ' . $this->city);
        }
        
        if ($this->state) {
            $parts[] = $this->state->name;
        }
        
        if ($this->country) {
            $parts[] = $this->country->name;
        }
        
        if ($this->additional_info) {
            $parts[] = $this->additional_info;
        }
        
        return implode(', ', array_filter($parts));
    }
} 