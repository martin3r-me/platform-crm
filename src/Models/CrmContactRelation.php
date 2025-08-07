<?php

namespace Platform\Crm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\Uid\UuidV7;

class CrmContactRelation extends Model
{
    protected $table = 'crm_contact_relations';
    
    protected $fillable = [
        'uuid',
        'contact_id',
        'company_id',
        'relation_type_id',
        'position',
        'notes',
        'start_date',
        'end_date',
        'is_primary',
        'is_active'
    ];
    
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
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
     * Beziehungen
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(CrmContact::class, 'contact_id');
    }
    
    public function company(): BelongsTo
    {
        return $this->belongsTo(CrmCompany::class, 'company_id');
    }
    
    public function relationType(): BelongsTo
    {
        return $this->belongsTo(CrmContactRelationType::class, 'relation_type_id');
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
    
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }
    
    public function scopeNotPrimary($query)
    {
        return $query->where('is_primary', false);
    }
    
    public function scopeForContact($query, $contactId)
    {
        return $query->where('contact_id', $contactId);
    }
    
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }
    
    public function scopeOfType($query, $relationTypeId)
    {
        return $query->where('relation_type_id', $relationTypeId);
    }
    
    public function scopeCurrent($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('end_date')
              ->orWhere('end_date', '>=', now()->toDateString());
        });
    }
    
    public function scopePast($query)
    {
        return $query->whereNotNull('end_date')
                    ->where('end_date', '<', now()->toDateString());
    }
    
    public function scopeStartingFrom($query, $date)
    {
        return $query->where('start_date', '>=', $date);
    }
    
    public function scopeEndingBefore($query, $date)
    {
        return $query->where('end_date', '<=', $date);
    }
    
    /**
     * Accessors
     */
    public function getIsCurrentAttribute(): bool
    {
        if (is_null($this->end_date)) {
            return true; // Kein Enddatum = aktiv
        }
        
        return $this->end_date->isFuture();
    }
    
    public function getIsPastAttribute(): bool
    {
        return !$this->is_current;
    }
    
    public function getDurationAttribute(): ?string
    {
        if (!$this->start_date) {
            return null;
        }
        
        $endDate = $this->end_date ?? now();
        $duration = $this->start_date->diffInDays($endDate);
        
        if ($duration < 30) {
            return $duration . ' Tage';
        } elseif ($duration < 365) {
            $months = round($duration / 30);
            return $months . ' Monate';
        } else {
            $years = round($duration / 365);
            return $years . ' Jahre';
        }
    }
    
    public function getDisplayTitleAttribute(): string
    {
        $parts = [];
        
        if ($this->position) {
            $parts[] = $this->position;
        }
        
        if ($this->relationType) {
            $parts[] = $this->relationType->name;
        }
        
        if (empty($parts)) {
            return 'Beziehung';
        }
        
        return implode(' - ', $parts);
    }
    
    /**
     * Helper-Methoden
     */
    public function isCurrent(): bool
    {
        return $this->is_current;
    }
    
    public function isPast(): bool
    {
        return $this->is_past;
    }
    
    public function isPrimary(): bool
    {
        return $this->is_primary;
    }
    
    public function markAsPrimary(): void
    {
        // Alle anderen primären Beziehungen für dieses Unternehmen deaktivieren
        self::where('company_id', $this->company_id)
            ->where('id', '!=', $this->id)
            ->update(['is_primary' => false]);
        
        // Diese Beziehung als primär markieren
        $this->update(['is_primary' => true]);
    }
    
    public function endRelation($endDate = null): void
    {
        $this->update([
            'end_date' => $endDate ?? now(),
            'is_primary' => false, // Primär-Kontakt kann nicht beendet werden
        ]);
    }
    
    public function reactivateRelation(): void
    {
        $this->update(['end_date' => null]);
    }
} 