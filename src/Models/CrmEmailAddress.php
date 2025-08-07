<?php

namespace Platform\Crm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Symfony\Component\Uid\UuidV7;

class CrmEmailAddress extends Model
{
    protected $table = 'crm_email_addresses';
    
    protected $fillable = [
        'uuid',
        'emailable_type',
        'emailable_id',
        'email_address',
        'notes',
        'email_type_id',
        'is_primary',
        'is_active',
        'is_verified',
        'verified_at'
    ];
    
    protected $casts = [
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
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
    public function emailable(): MorphTo
    {
        return $this->morphTo();
    }
    
    /**
     * Beziehung zum E-Mail-Typ
     */
    public function emailType(): BelongsTo
    {
        return $this->belongsTo(CrmEmailType::class, 'email_type_id');
    }
    
    /**
     * Scope für aktive E-Mail-Adressen
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    /**
     * Scope für inaktive E-Mail-Adressen
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }
    
    /**
     * Scope für primäre E-Mail-Adressen
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }
    
    /**
     * Scope für nicht-primäre E-Mail-Adressen
     */
    public function scopeNotPrimary($query)
    {
        return $query->where('is_primary', false);
    }
    
    /**
     * Scope für bestätigte E-Mail-Adressen
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }
    
    /**
     * Scope für nicht-bestätigte E-Mail-Adressen
     */
    public function scopeUnverified($query)
    {
        return $query->where('is_verified', false);
    }
    
    /**
     * Scope für bestimmten E-Mail-Typ
     */
    public function scopeOfType($query, $typeId)
    {
        return $query->where('email_type_id', $typeId);
    }
    
    /**
     * E-Mail-Adresse als String (Alias für email_address)
     */
    public function getEmailAttribute(): string
    {
        return $this->email_address;
    }
    
    /**
     * E-Mail-Adresse für Mailings (nur bestätigte)
     */
    public function getMailableEmailAttribute(): ?string
    {
        return $this->is_verified ? $this->email_address : null;
    }
    
    /**
     * E-Mail-Adresse bestätigen
     */
    public function markAsVerified(): void
    {
        $this->update([
            'is_verified' => true,
            'verified_at' => now(),
        ]);
    }
    
    /**
     * E-Mail-Adresse als unbestätigt markieren
     */
    public function markAsUnverified(): void
    {
        $this->update([
            'is_verified' => false,
            'verified_at' => null,
        ]);
    }
} 