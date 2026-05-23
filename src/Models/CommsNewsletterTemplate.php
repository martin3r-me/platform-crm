<?php

namespace Platform\Crm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Platform\ActivityLog\Traits\LogsActivity;
use Platform\Core\Models\Team;
use Platform\Core\Models\User;
use Symfony\Component\Uid\UuidV7;

class CommsNewsletterTemplate extends Model
{
    use SoftDeletes;
    use LogsActivity;

    protected $table = 'comms_newsletter_templates';

    protected $fillable = [
        'uuid',
        'team_id',
        'created_by_user_id',
        'name',
        'description',
        'category',
        'html_body',
        'text_body',
        'default_subject',
        'default_preheader',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
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
        return $this->belongsTo(Team::class, 'team_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function newsletters(): HasMany
    {
        return $this->hasMany(CommsNewsletter::class, 'newsletter_template_id');
    }
}
