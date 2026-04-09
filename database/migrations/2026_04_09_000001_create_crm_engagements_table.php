<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_engagements', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('type');              // note, call, meeting, task
            $table->string('title')->nullable();
            $table->longText('body')->nullable();
            $table->string('status')->nullable();     // planned, completed, cancelled
            $table->string('priority')->nullable();   // none, low, medium, high
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('owned_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['team_id', 'type']);
            $table->index(['team_id', 'scheduled_at']);
            $table->index(['team_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_engagements');
    }
};
