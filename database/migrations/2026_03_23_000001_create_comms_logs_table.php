<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comms_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_id')->index();
            $table->string('channel_type', 30)->index();
            $table->unsignedBigInteger('channel_id')->nullable();
            $table->string('event', 50)->index();
            $table->string('status', 20)->default('info');
            $table->text('summary');
            $table->json('details')->nullable();
            $table->string('recipient', 100)->nullable();
            $table->unsignedBigInteger('thread_id')->nullable();
            $table->unsignedBigInteger('message_id')->nullable();
            $table->unsignedBigInteger('triggered_by_user_id')->nullable();
            $table->string('source', 50)->nullable()->index();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comms_logs');
    }
};
