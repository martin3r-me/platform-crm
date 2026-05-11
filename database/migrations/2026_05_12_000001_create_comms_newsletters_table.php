<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comms_newsletters', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 36)->unique();
            $table->unsignedBigInteger('team_id')->index();
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->unsignedBigInteger('comms_channel_id')->nullable();
            $table->unsignedBigInteger('contact_list_id')->nullable();
            $table->string('name');
            $table->string('subject')->nullable();
            $table->string('preheader')->nullable();
            $table->longText('html_body')->nullable();
            $table->longText('text_body')->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->json('stats')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('team_id')->references('id')->on('teams')->cascadeOnDelete();
            $table->foreign('created_by_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('comms_channel_id')->references('id')->on('comms_channels')->nullOnDelete();
            $table->foreign('contact_list_id')->references('id')->on('crm_contact_lists')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comms_newsletters');
    }
};
