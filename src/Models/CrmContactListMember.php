<?php

namespace Platform\Crm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\Uid\UuidV7;

class CrmContactListMember extends Model
{
    protected $table = 'crm_contact_list_members';

    protected $fillable = [
        'uuid',
        'contact_list_id',
        'contact_id',
        'added_by_user_id',
        'notes',
        'status',
        'subscribed_at',
        'unsubscribed_at',
        'consent_source',
        'opt_in_confirmed_at',
        'doi_token',
    ];

    protected $casts = [
        'subscribed_at' => 'datetime',
        'unsubscribed_at' => 'datetime',
        'opt_in_confirmed_at' => 'datetime',
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

    // Scopes

    public function scopeSubscribed($query)
    {
        return $query->where('status', 'subscribed');
    }

    public function scopeUnsubscribed($query)
    {
        return $query->where('status', 'unsubscribed');
    }

    public function scopePendingDoi($query)
    {
        return $query->where('status', 'pending_doi');
    }

    // Helpers

    public function isSubscribed(): bool
    {
        return $this->status === 'subscribed';
    }

    public function isUnsubscribed(): bool
    {
        return $this->status === 'unsubscribed';
    }

    public function isPendingDoi(): bool
    {
        return $this->status === 'pending_doi';
    }

    // Relationships

    public function contactList(): BelongsTo
    {
        return $this->belongsTo(CrmContactList::class, 'contact_list_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(CrmContact::class, 'contact_id');
    }

    public function addedByUser(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\User::class, 'added_by_user_id');
    }
}
