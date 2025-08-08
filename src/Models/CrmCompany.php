<?php

namespace Platform\Crm\Models;

use Illuminate\Database\Eloquent\Model;
use Platform\ActivityLog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Symfony\Component\Uid\UuidV7;
use Platform\Crm\Models\CrmContactRelation;

class CrmCompany extends Model
{
    use LogsActivity;
    
    protected $table = 'crm_companies';
    
    protected $fillable = [
        'uuid',
        'name',
        'legal_name',
        'trading_name',
        'registration_number',
        'tax_number',
        'vat_number',
        'website',
        'description',
        'notes',
        'industry_id',
        'legal_form_id',
        'contact_status_id',
        'country_id',
        'created_by_user_id',
        'owned_by_user_id',
        'team_id',
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
     * Lookup-Beziehungen
     */
    public function industry(): BelongsTo
    {
        return $this->belongsTo(CrmIndustry::class, 'industry_id');
    }
    
    public function legalForm(): BelongsTo
    {
        return $this->belongsTo(CrmLegalForm::class, 'legal_form_id');
    }
    
    public function contactStatus(): BelongsTo
    {
        return $this->belongsTo(CrmContactStatus::class, 'contact_status_id');
    }
    
    public function country(): BelongsTo
    {
        return $this->belongsTo(CrmCountry::class, 'country_id');
    }
    
    /**
     * User/Team-Beziehungen
     */
    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by_user_id');
    }
    
    public function ownedByUser(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'owned_by_user_id');
    }
    
    public function team(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Team::class, 'team_id');
    }
    
    /**
     * Polymorphe Kommunikations-Beziehungen
     */
    public function postalAddresses(): MorphMany
    {
        return $this->morphMany(CrmPostalAddress::class, 'addressable');
    }
    
    public function phoneNumbers(): MorphMany
    {
        return $this->morphMany(CrmPhoneNumber::class, 'phoneable');
    }
    
    public function emailAddresses(): MorphMany
    {
        return $this->morphMany(CrmEmailAddress::class, 'emailable');
    }
    
    /**
     * Beziehungen zu Kontakten
     */
    public function contactRelations(): HasMany
    {
        return $this->hasMany(CrmContactRelation::class, 'company_id');
    }
    
    public function contacts()
    {
        return $this->belongsToMany(CrmContact::class, 'crm_contact_relations', 'company_id', 'contact_id')
                    ->withPivot(['relation_type_id', 'position', 'is_primary', 'start_date', 'end_date'])
                    ->withTimestamps();
    }
    
    public function primaryContacts()
    {
        return $this->contactRelations()->primary()->current();
    }
    
    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }
    
    public function scopeForTeam($query, $teamId)
    {
        return $query->where('team_id', $teamId);
    }
    
    public function scopeForUser($query, $userId)
    {
        return $query->where('owned_by_user_id', $userId);
    }
    
    public function scopePublic($query)
    {
        return $query->whereNull('owned_by_user_id');
    }
    
    public function scopePrivate($query)
    {
        return $query->whereNotNull('owned_by_user_id');
    }
    
    public function scopeByIndustry($query, $industryId)
    {
        return $query->where('industry_id', $industryId);
    }
    
    public function scopeByLegalForm($query, $legalFormId)
    {
        return $query->where('legal_form_id', $legalFormId);
    }
    
    public function scopeByCountry($query, $countryId)
    {
        return $query->where('country_id', $countryId);
    }
    
    /**
     * Accessors
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->trading_name) {
            return $this->trading_name;
        }
        
        if ($this->legal_name) {
            return $this->legal_name;
        }
        
        return $this->name;
    }
    
    public function getFullNameAttribute(): string
    {
        $parts = [];
        
        if ($this->trading_name) {
            $parts[] = $this->trading_name;
        }
        
        if ($this->legal_name && $this->legal_name !== $this->trading_name) {
            $parts[] = '(' . $this->legal_name . ')';
        }
        
        if (empty($parts)) {
            $parts[] = $this->name;
        }
        
        return implode(' ', array_filter($parts));
    }
    
    public function getPrimaryAddressAttribute()
    {
        return $this->postalAddresses()->primary()->first();
    }
    
    public function getPrimaryPhoneAttribute()
    {
        return $this->phoneNumbers()->primary()->first();
    }
    
    public function getPrimaryEmailAttribute()
    {
        return $this->emailAddresses()->primary()->first();
    }
    
    /**
     * Helper-Methoden
     */
    public function isPrivate(): bool
    {
        return !is_null($this->owned_by_user_id);
    }
    
    public function isPublic(): bool
    {
        return is_null($this->owned_by_user_id);
    }
    
    public function canBeViewedByUser($user): bool
    {
        // Öffentliche Unternehmen können alle Team-Mitglieder sehen
        if ($this->isPublic()) {
            return true;
        }
        
        // Private Unternehmen nur vom Besitzer
        return $this->owned_by_user_id === $user->id;
    }
    
    public function hasVatNumber(): bool
    {
        return !empty($this->vat_number);
    }
    
    public function hasTaxNumber(): bool
    {
        return !empty($this->tax_number);
    }
    
    public function hasRegistrationNumber(): bool
    {
        return !empty($this->registration_number);
    }
} 