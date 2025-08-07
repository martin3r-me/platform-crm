<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('crm_contact_links', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('contact_id')->constrained('crm_contacts')->cascadeOnDelete();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();

            $table->unsignedBigInteger('linkable_id');
            $table->string('linkable_type'); // z. B. CommsChannelEmailThread, Ticket, Employee

            $table->timestamps();

            $table->unique(['contact_id', 'linkable_type', 'linkable_id'], 'contact_link_unique');
            $table->index(['team_id']);
            $table->index(['linkable_type', 'linkable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_contact_links');
    }
}; 