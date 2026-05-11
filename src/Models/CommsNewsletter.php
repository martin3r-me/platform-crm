<?php

namespace Platform\Crm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Platform\ActivityLog\Traits\LogsActivity;
use Platform\Core\Models\Team;
use Platform\Core\Models\User;
use Symfony\Component\Uid\UuidV7;

class CommsNewsletter extends Model
{
    use SoftDeletes;
    use LogsActivity;

    protected $table = 'comms_newsletters';

    protected $fillable = [
        'uuid',
        'team_id',
        'created_by_user_id',
        'comms_channel_id',
        'name',
        'subject',
        'preheader',
        'html_body',
        'text_body',
        'status',
        'scheduled_at',
        'sent_at',
        'stats',
    ];

    protected $casts = [
        'stats' => 'array',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
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
            if (empty($model->status)) {
                $model->status = 'draft';
            }
        });
    }

    // Scopes

    public function scopeForTeam($query, $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
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

    public function channel(): BelongsTo
    {
        return $this->belongsTo(CommsChannel::class, 'comms_channel_id');
    }

    public function contactLists(): BelongsToMany
    {
        return $this->belongsToMany(CrmContactList::class, 'comms_newsletter_contact_lists', 'newsletter_id', 'contact_list_id')
            ->withTimestamps();
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(CommsNewsletterRecipient::class, 'newsletter_id');
    }

    // Helpers

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isScheduled(): bool
    {
        return $this->status === 'scheduled';
    }

    public function isSending(): bool
    {
        return $this->status === 'sending';
    }

    public function isSent(): bool
    {
        return $this->status === 'sent';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function canEdit(): bool
    {
        return in_array($this->status, ['draft', 'scheduled']);
    }

    public function updateStats(): void
    {
        $recipients = $this->recipients();

        $this->update(['stats' => [
            'total' => $recipients->count(),
            'pending' => $recipients->where('status', 'pending')->count(),
            'sent' => $recipients->where('status', 'sent')->count(),
            'delivered' => $recipients->where('status', 'delivered')->count(),
            'opened' => $recipients->whereNotNull('opened_at')->count(),
            'clicked' => $recipients->whereNotNull('clicked_at')->count(),
            'bounced' => $recipients->where('status', 'bounced')->count(),
            'unsubscribed' => $recipients->where('status', 'unsubscribed')->count(),
            'failed' => $recipients->where('status', 'failed')->count(),
        ]]);
    }
}
