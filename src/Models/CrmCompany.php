<?php

namespace Platform\Crm\Models;

use Illuminate\Database\Eloquent\Model;
use Platform\ActivityLog\Traits\LogsActivity;
use Platform\Crm\Contracts\CompanyInterface;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Symfony\Component\Uid\UuidV7;
use Platform\Crm\Models\CrmContactRelation;

class CrmCompany extends Model implements CompanyInterface
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
        return $this->belongsTo(\Platform\Core\Models\User::class, 'created_by_user_id');
    }
    
    public function ownedByUser(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\User::class, 'owned_by_user_id');
    }
    
    public function team(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\Team::class, 'team_id');
    }
    
    /**
     * Polymorphe Company-Links
     */
    public function companyLinks(): MorphMany
    {
        return $this->hasMany(CrmCompanyLink::class, 'company_id');
    }
    
    /**
     * Verknüpfte Models über Company-Links
     */
    public function linkedModels(string $linkableType): \Illuminate\Database\Eloquent\Relations\MorphToMany
    {
        return $this->morphedByMany(
            $linkableType,
            'linkable',
            'crm_company_links',
            'company_id',
            'linkable_id'
        );
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
     * Interface-Implementierung
     */
    public function getCompanyId(): int
    {
        return $this->id;
    }
    
    public function getName(): string
    {
        return $this->name;
    }
    
    public function getLegalName(): ?string
    {
        return $this->legal_name;
    }
    
    public function getTradingName(): ?string
    {
        return $this->trading_name;
    }
    
    public function getDisplayName(): string
    {
        return $this->display_name;
    }
    
    public function getFullName(): string
    {
        return $this->full_name;
    }
    
    public function getRegistrationNumber(): ?string
    {
        return $this->registration_number;
    }
    
    public function getTaxNumber(): ?string
    {
        return $this->tax_number;
    }
    
    public function getVatNumber(): ?string
    {
        return $this->vat_number;
    }
    
    public function getWebsite(): ?string
    {
        return $this->website;
    }
    
    public function getEmailAddresses(): array
    {
        return $this->emailAddresses()
            ->active()
            ->get()
            ->map(function ($email) {
                return [
                    'email' => $email->email_address,
                    'type' => $email->emailType?->name,
                    'is_primary' => $email->is_primary,
                ];
            })
            ->toArray();
    }
    
    public function getPhoneNumbers(): array
    {
        return $this->phoneNumbers()
            ->active()
            ->get()
            ->map(function ($phone) {
                return [
                    'number' => $phone->international,
                    'type' => $phone->phoneType?->name,
                    'is_primary' => $phone->is_primary,
                ];
            })
            ->toArray();
    }
    
    public function getPostalAddresses(): array
    {
        return $this->postalAddresses()
            ->active()
            ->get()
            ->map(function ($address) {
                return [
                    'street' => $address->street,
                    'house_number' => $address->house_number,
                    'postal_code' => $address->postal_code,
                    'city' => $address->city,
                    'country' => $address->country?->name,
                    'type' => $address->addressType?->name,
                    'is_primary' => $address->is_primary,
                ];
            })
            ->toArray();
    }
    
    public function getTeamId(): int
    {
        return $this->team_id;
    }
    
    public function isActive(): bool
    {
        return $this->is_active;
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
    
    /**
     * Contact-Links Beziehung (für Loose Coupling mit anderen Modulen)
     */
    public function contactLinks(): HasMany
    {
        return $this->hasMany(CrmContactLink::class, 'company_id');
    }
    
    /**
     * Interface-Implementierung für Loose Coupling
     */
    public function getLinkedEntitiesCount(string $entityType): int
    {
        return $this->contactLinks()
            ->where('linkable_type', $entityType)
            ->count();
    }
    
    public function hasLinkedEntity(string $entityType, int $entityId): bool
    {
        return $this->contactLinks()
            ->where('linkable_type', $entityType)
            ->where('linkable_id', $entityId)
            ->exists();
    }
    
    public function linkEntity(string $entityType, int $entityId, array $additionalData = []): void
    {
        // Diese Methode würde über Contact-Links implementiert werden
        // Für HCM-Mitarbeiter über HasEmployeeContact Trait
    }
    
    public function unlinkEntity(string $entityType, int $entityId): void
    {
        $this->contactLinks()
            ->where('linkable_type', $entityType)
            ->where('linkable_id', $entityId)
            ->delete();
    }

    /**
     * HCM-spezifische Interface-Methoden für Loose Coupling
     */
    public function getHcmEmployeesCount(): int
    {
        return $this->getLinkedEntitiesCount('Platform\\Hcm\\Models\\HcmEmployee');
    }

    public function hasHcmEmployees(): bool
    {
        return $this->getHcmEmployeesCount() > 0;
    }

    public function getHcmEmployeesWithCompanyNumbersCount(): int
    {
        // Hole alle HCM-Mitarbeiter für dieses Unternehmen
        $employeeIds = $this->contactLinks()
            ->where('linkable_type', 'Platform\\Hcm\\Models\\HcmEmployee')
            ->pluck('linkable_id');
            
        // Zähle Mitarbeiter mit employee_number (aus HCM-Modell)
        return \Platform\Hcm\Models\HcmEmployee::whereIn('id', $employeeIds)
            ->whereNotNull('employee_number')
            ->where('employee_number', '!=', '')
            ->count();
    }
} 