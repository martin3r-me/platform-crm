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
        'verified_at',
        'whatsapp_status',
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

    // ========================================
    // WhatsApp Status
    // ========================================

    /**
     * WhatsApp Status Konstanten
     */
    public const WHATSAPP_UNKNOWN = 'unknown';
    public const WHATSAPP_AVAILABLE = 'available';
    public const WHATSAPP_UNAVAILABLE = 'unavailable';
    public const WHATSAPP_OPTED_IN = 'opted_in';

    /**
     * Scope für WhatsApp-verfügbare Nummern
     */
    public function scopeWhatsappAvailable($query)
    {
        return $query->whereIn('whatsapp_status', [self::WHATSAPP_AVAILABLE, self::WHATSAPP_OPTED_IN]);
    }

    /**
     * Scope für WhatsApp-nicht-verfügbare Nummern
     */
    public function scopeWhatsappUnavailable($query)
    {
        return $query->where('whatsapp_status', self::WHATSAPP_UNAVAILABLE);
    }

    /**
     * Scope für ungeprüfte WhatsApp-Nummern
     */
    public function scopeWhatsappUnknown($query)
    {
        return $query->where('whatsapp_status', self::WHATSAPP_UNKNOWN);
    }

    /**
     * Scope für Nummern mit WhatsApp Opt-In
     */
    public function scopeWhatsappOptedIn($query)
    {
        return $query->where('whatsapp_status', self::WHATSAPP_OPTED_IN);
    }

    /**
     * WhatsApp als verfügbar markieren (Nachricht erfolgreich zugestellt)
     */
    public function markWhatsappAvailable(): void
    {
        // Nicht überschreiben wenn bereits opted_in
        if ($this->whatsapp_status === self::WHATSAPP_OPTED_IN) {
            return;
        }
        $this->update(['whatsapp_status' => self::WHATSAPP_AVAILABLE]);
    }

    /**
     * WhatsApp als nicht verfügbar markieren (Zustellung fehlgeschlagen)
     */
    public function markWhatsappUnavailable(): void
    {
        $this->update(['whatsapp_status' => self::WHATSAPP_UNAVAILABLE]);
    }

    /**
     * WhatsApp Opt-In markieren (Nutzer hat selbst geschrieben)
     */
    public function markWhatsappOptedIn(): void
    {
        $this->update(['whatsapp_status' => self::WHATSAPP_OPTED_IN]);
    }

    /**
     * WhatsApp Status zurücksetzen
     */
    public function resetWhatsappStatus(): void
    {
        $this->update(['whatsapp_status' => self::WHATSAPP_UNKNOWN]);
    }

    /**
     * Prüft ob WhatsApp verfügbar ist
     */
    public function isWhatsappAvailable(): bool
    {
        return in_array($this->whatsapp_status, [self::WHATSAPP_AVAILABLE, self::WHATSAPP_OPTED_IN]);
    }

    /**
     * Prüft ob WhatsApp definitiv nicht verfügbar ist
     */
    public function isWhatsappUnavailable(): bool
    {
        return $this->whatsapp_status === self::WHATSAPP_UNAVAILABLE;
    }

    /**
     * Prüft ob WhatsApp Status unbekannt ist
     */
    public function isWhatsappUnknown(): bool
    {
        return $this->whatsapp_status === self::WHATSAPP_UNKNOWN;
    }

    /**
     * Label für WhatsApp Status
     */
    public function getWhatsappStatusLabelAttribute(): string
    {
        return match ($this->whatsapp_status) {
            self::WHATSAPP_AVAILABLE => 'WhatsApp verfügbar',
            self::WHATSAPP_UNAVAILABLE => 'Kein WhatsApp',
            self::WHATSAPP_OPTED_IN => 'WhatsApp Opt-In',
            default => 'Unbekannt',
        };
    }

    /**
     * Icon/Badge für WhatsApp Status
     */
    public function getWhatsappStatusIconAttribute(): string
    {
        return match ($this->whatsapp_status) {
            self::WHATSAPP_AVAILABLE => '✅',
            self::WHATSAPP_UNAVAILABLE => '❌',
            self::WHATSAPP_OPTED_IN => '💬',
            default => '❓',
        };
    }
} 