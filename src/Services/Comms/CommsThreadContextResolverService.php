<?php

namespace Platform\Crm\Services\Comms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Log;
use Platform\Core\Contracts\CommsContextAwareResolverInterface;
use Platform\Core\Services\Comms\ContactResolverRegistry;
use Platform\Crm\Models\CommsEmailThread;
use Platform\Crm\Models\CommsThreadContext;
use Platform\Crm\Models\CommsWhatsAppThread;

class CommsThreadContextResolverService
{
    /**
     * Resolve and assign context(s) to a thread via the pivot table.
     *
     * Guard: only runs if thread has a contact but no contexts yet.
     *
     * @return bool Whether at least one context was assigned
     */
    public function resolveAndAssignContext(Model $thread): bool
    {
        // Guard: thread must have contact
        if (!$thread->contact_id || !$thread->contact_type) {
            return false;
        }

        // Guard: skip if thread already has contexts in pivot table
        if ($thread->hasAnyContext()) {
            return false;
        }

        // Strategy A: Sibling threads
        $resolved = $this->resolveFromSiblingThreads($thread);
        if ($resolved) {
            return true;
        }

        // Strategy B: Contact-as-context
        return $this->resolveContactAsContext($thread);
    }

    /**
     * Strategy A: Look at sibling threads (same contact, different threads)
     * across both email and whatsapp channels for existing contexts.
     */
    protected function resolveFromSiblingThreads(Model $thread): bool
    {
        $contactVariants = $this->getModelVariants($thread->contact_type);

        // Collect all contexts from sibling threads (both email + whatsapp)
        $siblingContexts = CommsThreadContext::query()
            ->where(function ($q) use ($thread, $contactVariants) {
                // Email thread siblings
                $q->whereIn('thread_id', function ($sub) use ($thread, $contactVariants) {
                    $sub->select('id')
                        ->from('comms_email_threads')
                        ->where('deleted_at', null)
                        ->where('id', '!=', $thread instanceof CommsEmailThread ? $thread->id : 0)
                        ->where(function ($contactQ) use ($thread, $contactVariants) {
                            foreach ($contactVariants as $variant) {
                                $contactQ->orWhere(function ($q2) use ($variant, $thread) {
                                    $q2->where('contact_type', $variant)
                                       ->where('contact_id', $thread->contact_id);
                                });
                            }
                        });
                })->where('thread_type', CommsEmailThread::class);

                // WhatsApp thread siblings
                $q->orWhere(function ($q2) use ($thread, $contactVariants) {
                    $q2->whereIn('thread_id', function ($sub) use ($thread, $contactVariants) {
                        $sub->select('id')
                            ->from('comms_whatsapp_threads')
                            ->where('deleted_at', null)
                            ->where('id', '!=', $thread instanceof CommsWhatsAppThread ? $thread->id : 0)
                            ->where(function ($contactQ) use ($thread, $contactVariants) {
                                foreach ($contactVariants as $variant) {
                                    $contactQ->orWhere(function ($q3) use ($variant, $thread) {
                                        $q3->where('contact_type', $variant)
                                           ->where('contact_id', $thread->contact_id);
                                    });
                                }
                            });
                    })->where('thread_type', CommsWhatsAppThread::class);
                });
            })
            ->get();

        if ($siblingContexts->isEmpty()) {
            return false;
        }

        // Group by unique context (normalize variants to canonical form)
        $uniqueContexts = $siblingContexts
            ->map(fn ($ctx) => $ctx->context_model . ':' . $ctx->context_model_id)
            ->unique()
            ->values();

        if ($uniqueContexts->count() === 1) {
            // Unanimous: all siblings agree on the same context
            $first = $siblingContexts->first();
            $thread->addContext($first->context_model, $first->context_model_id, 'sibling');

            Log::debug('[CommsThreadContextResolver] Context resolved from sibling (unanimous)', [
                'thread_type' => get_class($thread),
                'thread_id' => $thread->id,
                'context_model' => $first->context_model,
                'context_model_id' => $first->context_model_id,
            ]);

            // Also set legacy column for backward compat
            $this->setLegacyContext($thread, $first->context_model, $first->context_model_id);

            return true;
        }

        // Ambiguous: multiple different contexts found – do NOT assign any.
        // Assigning all would pollute every context with unrelated threads.
        // The thread stays unassigned until explicitly sent from a specific context.
        Log::info('[CommsThreadContextResolver] Ambiguous sibling contexts, skipping auto-assign', [
            'thread_type' => get_class($thread),
            'thread_id' => $thread->id,
            'context_count' => $uniqueContexts->count(),
        ]);

        return false;
    }

    /**
     * Strategy B: Use the contact itself as context if the resolver declares it eligible.
     */
    protected function resolveContactAsContext(Model $thread): bool
    {
        $contactType = $thread->contact_type;
        $contactId = $thread->contact_id;

        if (!$contactType || !$contactId) {
            return false;
        }

        // Check if any registered resolver declares this contact type as context-eligible
        $registry = app(ContactResolverRegistry::class);
        $eligible = false;

        foreach ($registry->all() as $resolver) {
            if ($resolver instanceof CommsContextAwareResolverInterface) {
                $eligibleTypes = $resolver->getContextEligibleContactTypes();

                // Check against variants (class ↔ morph alias)
                $contactVariants = $this->getModelVariants($contactType);
                foreach ($contactVariants as $variant) {
                    if (in_array($variant, $eligibleTypes, true)) {
                        $eligible = true;
                        break 2;
                    }
                }
            }
        }

        if (!$eligible) {
            return false;
        }

        $thread->addContext($contactType, $contactId, 'contact_as_context');

        Log::debug('[CommsThreadContextResolver] Context resolved as contact-as-context', [
            'thread_type' => get_class($thread),
            'thread_id' => $thread->id,
            'context_model' => $contactType,
            'context_model_id' => $contactId,
        ]);

        // Legacy column
        $this->setLegacyContext($thread, $contactType, $contactId);

        return true;
    }

    /**
     * Get both morph alias and full class name variants for a model type string.
     *
     * @return array<string>
     */
    protected function getModelVariants(string $modelType): array
    {
        $variants = [$modelType];

        if (!str_contains($modelType, '\\')) {
            $fullClass = Relation::getMorphedModel($modelType);
            if ($fullClass && $fullClass !== $modelType) {
                $variants[] = $fullClass;
            }
        } else {
            $morphMap = Relation::morphMap();
            $alias = array_search($modelType, $morphMap, true);
            if ($alias !== false) {
                $variants[] = $alias;
            }
        }

        return array_unique($variants);
    }

    /**
     * Set the legacy context_model/context_model_id columns for backward compat.
     */
    protected function setLegacyContext(Model $thread, string $contextModel, int $contextModelId): void
    {
        if (!$thread->context_model) {
            $thread->updateQuietly([
                'context_model' => $contextModel,
                'context_model_id' => $contextModelId,
            ]);
        }
    }
}
