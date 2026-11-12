<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Generischer CRM-Lookup "contact_sources" (Herkunft eines Kontakts).
 *
 * Bewusst OHNE Seed ausgeliefert: Die konkreten Quellen sind Mandanten-
 * Fachdaten und werden im UI bzw. per MCP als Daten gepflegt – nicht als
 * Hardcode ins geteilte Modul.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_contact_sources', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('code', 60)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_contact_sources');
    }
};
