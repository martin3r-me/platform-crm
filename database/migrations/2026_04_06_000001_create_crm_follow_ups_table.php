<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_follow_ups', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->morphs('followupable');
            $table->string('title', 255);
            $table->date('due_date');
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['followupable_type', 'followupable_id', 'completed_at']);
            $table->index(['team_id', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_follow_ups');
    }
};
