<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comms_thread_contexts', function (Blueprint $table) {
            $table->id();
            $table->string('thread_type');   // e.g. CommsEmailThread class or morph alias
            $table->unsignedBigInteger('thread_id');
            $table->string('context_model'); // e.g. CrmContact class or morph alias
            $table->unsignedBigInteger('context_model_id');
            $table->string('source')->nullable(); // 'outbound', 'sibling', 'contact_as_context', 'manual'
            $table->timestamp('created_at')->useCurrent();

            $table->unique(
                ['thread_type', 'thread_id', 'context_model', 'context_model_id'],
                'comms_thread_contexts_unique'
            );
            $table->index(['context_model', 'context_model_id'], 'comms_thread_contexts_context_idx');
            $table->index(['thread_type', 'thread_id'], 'comms_thread_contexts_thread_idx');
        });

        // Migrate existing context data from email threads
        DB::statement("
            INSERT INTO comms_thread_contexts (thread_type, thread_id, context_model, context_model_id, source)
            SELECT
                '" . addslashes(\Platform\Crm\Models\CommsEmailThread::class) . "',
                id,
                context_model,
                context_model_id,
                'legacy'
            FROM comms_email_threads
            WHERE context_model IS NOT NULL
              AND context_model_id IS NOT NULL
              AND deleted_at IS NULL
        ");

        // Migrate existing context data from whatsapp threads
        DB::statement("
            INSERT INTO comms_thread_contexts (thread_type, thread_id, context_model, context_model_id, source)
            SELECT
                '" . addslashes(\Platform\Crm\Models\CommsWhatsAppThread::class) . "',
                id,
                context_model,
                context_model_id,
                'legacy'
            FROM comms_whatsapp_threads
            WHERE context_model IS NOT NULL
              AND context_model_id IS NOT NULL
              AND deleted_at IS NULL
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('comms_thread_contexts');
    }
};
