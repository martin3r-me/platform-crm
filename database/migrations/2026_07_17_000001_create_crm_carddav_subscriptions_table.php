<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_carddav_subscriptions', function (Blueprint $table) {
            $table->id();
            // Der die CardDAV-Verbindung ausgebende User (dessen Sichtbarkeit gilt).
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            // Abonniertes Adressbuch. Null = alle für den User sichtbaren Listen.
            $table->foreignId('contact_list_id')->nullable()
                ->constrained('crm_contact_lists')->cascadeOnDelete();
            // Basic-Auth-Passwort des Clients. Identifiziert das Abo eindeutig.
            $table->string('secret', 64)->unique();
            $table->string('name', 255);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'team_id']);
            $table->index('contact_list_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_carddav_subscriptions');
    }
};
