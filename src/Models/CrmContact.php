<?php

namespace Platform\Crm\Models;

use Illuminate\Database\Eloquent\Model;
use Platform\ActivityLog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Symfony\Component\Uid\UuidV7;
use Platform\Crm\Models\CrmContactRelation;

class CrmContact extends Model
{
    use LogsActivity;
    
    protected $table = 'crm_contacts';
    
    protected $fillable = [
        'uuid',
        'first_name',
        'last_name',
        'middle_name',
        'nickname',
        'birth_date',
        'notes',
        'salutation_id',
        'academic_title_id',
        'gender_id',
        'language_id',
        'contact_status_id',
        'created_by_user_id',
        'owned_by_user_id',
        'team_id',
        'is_active'
    ];
    
    protected $casts = [
        'birth_date' => 'date',
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
    public function salutation(): BelongsTo
    {
        return $this->belongsTo(CrmSalutation::class, 'salutation_id');
    }
    
    public function academicTitle(): BelongsTo
    {
        return $this->belongsTo(CrmAcademicTitle::class, 'academic_title_id');
    }
    
    public function gender(): BelongsTo
    {
        return $this->belongsTo(CrmGender::class, 'gender_id');
    }
    
    public function language(): BelongsTo
    {
        return $this->belongsTo(CrmLanguage::class, 'language_id');
    }
    
    public function contactStatus(): BelongsTo
    {
        return $this->belongsTo(CrmContactStatus::class, 'contact_status_id');
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
     * Kontakt-Beziehungen zu Unternehmen
     */
    public function contactRelations(): HasMany
    {
        return $this->hasMany(CrmContactRelation::class, 'contact_id');
    }
    
    /**
     * Unternehmen-Beziehungen (veraltet, verwende contactRelations)
     */
    public function companyRelations(): HasMany
    {
        return $this->hasMany(CrmContactRelation::class, 'contact_id');
    }
    
    public function companies()
    {
        return $this->belongsToMany(CrmCompany::class, 'crm_contact_relations', 'contact_id', 'company_id')
                    ->withPivot(['relation_type_id', 'position', 'is_primary', 'start_date', 'end_date'])
                    ->withTimestamps();
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
    
    /**
     * Accessors
     */
    public function getFullNameAttribute(): string
    {
        $parts = [];
        
        if ($this->academicTitle) {
            $parts[] = $this->academicTitle->name;
        }
        
        if ($this->salutation) {
            $parts[] = $this->salutation->name;
        }
        
        $parts[] = $this->first_name;
        
        if ($this->middle_name) {
            $parts[] = $this->middle_name;
        }
        
        $parts[] = $this->last_name;
        
        return implode(' ', array_filter($parts));
    }
    
    public function getDisplayNameAttribute(): string
    {
        if ($this->nickname) {
            return $this->nickname;
        }
        
        return $this->first_name . ' ' . $this->last_name;
    }
    
    public function getAgeAttribute(): ?int
    {
        if (!$this->birth_date) {
            return null;
        }
        
        return $this->birth_date->age;
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
    
    public function isVisible(): bool
    {
        // Prüfe ob Kontakt aktiv ist
        if (!$this->is_active) {
            return false;
        }
        
        // Prüfe Team-Zugehörigkeit
        if ($this->team_id !== auth()->user()->current_team_id) {
            return false;
        }
        
        // Öffentliche Kontakte können alle Team-Mitglieder sehen
        if ($this->isPublic()) {
            return true;
        }
        
        // Private Kontakte nur vom Besitzer
        return $this->owned_by_user_id === auth()->id();
    }
    
    public function canBeViewedByUser($user): bool
    {
        // Öffentliche Kontakte können alle Team-Mitglieder sehen
        if ($this->isPublic()) {
            return true;
        }
        
        // Private Kontakte nur vom Besitzer
        return $this->owned_by_user_id === $user->id;
    }
} 