<?php

namespace Platform\Crm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CommsThreadContext extends Model
{
    public $timestamps = false;

    protected $table = 'comms_thread_contexts';

    protected $fillable = [
        'thread_type',
        'thread_id',
        'context_model',
        'context_model_id',
        'source',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function thread(): MorphTo
    {
        return $this->morphTo('thread', 'thread_type', 'thread_id');
    }

    public function context(): MorphTo
    {
        return $this->morphTo('context', 'context_model', 'context_model_id');
    }
}
