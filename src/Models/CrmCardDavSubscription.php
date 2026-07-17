<?php

namespace Platform\Crm\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Ein abonnierbares CardDAV-Adressbuch für einen User.
 *
 * Das {@see $secret} ist das Basic-Auth-Passwort, das der Client sendet; es
 * identifiziert das Abo eindeutig und bindet die Sichtbarkeit an {@see $user}
 * (Team + Owner-Scope). Siehe docs/carddav.md.
 */
class CrmCardDavSubscription extends Model
{
    protected $table = 'crm_carddav_subscriptions';

    protected $fillable = [
        'user_id',
        'team_id',
        'contact_list_id',
        'secret',
        'name',
        'last_used_at',
        'expires_at',
        'revoked_at',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
        'expires_at'   => 'datetime',
        'revoked_at'   => 'datetime',
    ];

    /**
     * Das Secret nie versehentlich mit ausgeben (z. B. in JSON/Logs).
     */
    protected $hidden = [
        'secret',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->secret)) {
                do {
                    $secret = Str::random(64);
                } while (self::where('secret', $secret)->exists());

                $model->secret = $secret;
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\User::class, 'user_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\Team::class, 'team_id');
    }

    public function contactList(): BelongsTo
    {
        return $this->belongsTo(CrmContactList::class, 'contact_list_id');
    }

    /**
     * Aktiv = nicht widerrufen und nicht abgelaufen.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->whereNull('revoked_at')
            ->where(function (Builder $q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }

    public function isActive(): bool
    {
        if ($this->revoked_at !== null) {
            return false;
        }

        return $this->expires_at === null || $this->expires_at->isFuture();
    }

    public function markUsed(): void
    {
        // Ohne Model-Events / updated_at-Rauschen, nur der Zeitstempel.
        $this->forceFill(['last_used_at' => now()])->saveQuietly();
    }

    public function revoke(): void
    {
        $this->forceFill(['revoked_at' => now()])->save();
    }
}
