<?php

namespace Platform\Crm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Symfony\Component\Uid\UuidV7;

class CrmContactLink extends Model
{
    protected $table = 'crm_contact_links';

    protected $fillable = [
        'uuid',
        'contact_id',
        'linkable_id',
        'linkable_type',
        'team_id',
        'created_by_user_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
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

            // Team-Kontext setzen
            if (empty($model->team_id) && auth()->check()) {
                $model->team_id = auth()->user()->current_team_id;
            }

            // Created by setzen
            if (empty($model->created_by_user_id) && auth()->check()) {
                $model->created_by_user_id = auth()->id();
            }
        });
    }

    /**
     * Scope für aktuelles Team
     */
    public function scopeForCurrentTeam($query)
    {
        return $query->where('team_id', auth()->user()->current_team_id);
    }

    /**
     * Scope für sichtbare Links (dynamische Prüfung über Kontakt)
     */
    public function scopeVisible($query)
    {
        return $query->with('contact')->get()->filter(function ($link) {
            return $link->contact && $link->contact->isVisible();
        });
    }

    /**
     * Zugehöriger Kontakt
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(CrmContact::class, 'contact_id');
    }

    /**
     * Verlinkte Entität (Thread, Ticket, etc.)
     */
    public function linkable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Erstellt von User
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by_user_id');
    }

    /**
     * Team
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Team::class, 'team_id');
    }

    /**
     * Scope für spezifische Linkable-Typen
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('linkable_type', $type);
    }

    /**
     * Scope für spezifische Kontakte
     */
    public function scopeForContact($query, $contactId)
    {
        return $query->where('contact_id', $contactId);
    }
} 