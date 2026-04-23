<?php

namespace Platform\Crm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Platform\ActivityLog\Traits\LogsActivity;
use Symfony\Component\Uid\UuidV7;

class CrmContactList extends Model
{
    use SoftDeletes;
    use LogsActivity;

    protected $table = 'crm_contact_lists';

    protected $fillable = [
        'uuid',
        'name',
        'description',
        'color',
        'is_active',
        'member_count',
        'created_by_user_id',
        'owned_by_user_id',
        'team_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'member_count' => 'integer',
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

    public function scopeForTeam($query, $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Relationships

    public function team(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\Team::class, 'team_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\User::class, 'created_by_user_id');
    }

    public function ownedByUser(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\User::class, 'owned_by_user_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(CrmContactListMember::class, 'contact_list_id');
    }

    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(CrmContact::class, 'crm_contact_list_members', 'contact_list_id', 'contact_id')
            ->withPivot(['notes', 'added_by_user_id'])
            ->withTimestamps();
    }

    // Helpers

    public function updateMemberCount(): void
    {
        $this->update(['member_count' => $this->members()->count()]);
    }
}
