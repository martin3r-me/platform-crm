<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comms_newsletter_templates', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('team_id');
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->longText('html_body')->nullable();
            $table->longText('text_body')->nullable();
            $table->string('default_subject')->nullable();
            $table->string('default_preheader')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('team_id')->references('id')->on('teams')->cascadeOnDelete();
            $table->foreign('created_by_user_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['team_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comms_newsletter_templates');
    }
};
