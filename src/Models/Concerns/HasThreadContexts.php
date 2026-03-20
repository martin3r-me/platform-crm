<?php

namespace Platform\Crm\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use Platform\Crm\Models\CommsThreadContext;

trait HasThreadContexts
{
    public function contexts(): HasMany
    {
        return $this->hasMany(CommsThreadContext::class, 'thread_id')
            ->where('thread_type', static::class);
    }

    /**
     * Add a context to this thread (deduplicated).
     *
     * @return CommsThreadContext The existing or newly created pivot record
     */
    public function addContext(string $contextModel, int $contextModelId, ?string $source = null): CommsThreadContext
    {
        return CommsThreadContext::firstOrCreate(
            [
                'thread_type' => static::class,
                'thread_id' => $this->id,
                'context_model' => $contextModel,
                'context_model_id' => $contextModelId,
            ],
            [
                'source' => $source,
            ]
        );
    }

    /**
     * Check if this thread has a specific context.
     */
    public function hasContextFor(string $contextModel, ?int $contextModelId = null): bool
    {
        $variants = static::getContextModelVariantsFor($contextModel);

        $query = CommsThreadContext::query()
            ->where('thread_type', static::class)
            ->where('thread_id', $this->id)
            ->where(function ($q) use ($variants, $contextModelId) {
                foreach ($variants as $variant) {
                    $q->orWhere(function ($q2) use ($variant, $contextModelId) {
                        $q2->where('context_model', $variant);
                        if ($contextModelId !== null) {
                            $q2->where('context_model_id', $contextModelId);
                        }
                    });
                }
            });

        return $query->exists();
    }

    /**
     * Check if this thread has any contexts at all.
     */
    public function hasAnyContext(): bool
    {
        return CommsThreadContext::query()
            ->where('thread_type', static::class)
            ->where('thread_id', $this->id)
            ->exists();
    }

    /**
     * Get both morph alias and full class name variants for a context model string.
     *
     * @return array<string>
     */
    public static function getContextModelVariantsFor(string $contextModel): array
    {
        $variants = [$contextModel];

        if (!str_contains($contextModel, '\\')) {
            // It's a morph alias → resolve to full class
            $fullClass = Relation::getMorphedModel($contextModel);
            if ($fullClass && $fullClass !== $contextModel) {
                $variants[] = $fullClass;
            }
        } else {
            // It's a full class → find its morph alias
            $morphMap = Relation::morphMap();
            $alias = array_search($contextModel, $morphMap, true);
            if ($alias !== false) {
                $variants[] = $alias;
            }
        }

        return array_unique($variants);
    }

    /**
     * Query scope: filter threads that have a specific context in the pivot table.
     */
    public function scopeForContext(Builder $query, string $contextModel, int $contextModelId): void
    {
        $variants = static::getContextModelVariantsFor($contextModel);
        $threadClass = static::class;
        $table = (new static)->getTable();

        $query->whereExists(function ($sub) use ($variants, $contextModelId, $threadClass, $table) {
            $sub->select(DB::raw(1))
                ->from('comms_thread_contexts')
                ->whereColumn('comms_thread_contexts.thread_id', "{$table}.id")
                ->where('comms_thread_contexts.thread_type', $threadClass)
                ->where(function ($q) use ($variants, $contextModelId) {
                    foreach ($variants as $variant) {
                        $q->orWhere(function ($q2) use ($variant, $contextModelId) {
                            $q2->where('comms_thread_contexts.context_model', $variant)
                               ->where('comms_thread_contexts.context_model_id', $contextModelId);
                        });
                    }
                });
        });
    }

    /**
     * Count threads for a specific context on a given channel.
     */
    public static function countForContext(int $channelId, string $contextModel, int $contextModelId): int
    {
        return static::query()
            ->where('comms_channel_id', $channelId)
            ->forContext($contextModel, $contextModelId)
            ->count();
    }
}
