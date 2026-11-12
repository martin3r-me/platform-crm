<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Generischer CRM-Lookup "segments" (Segmentierung / Verticals).
 *
 * Bewusst OHNE Seed ausgeliefert: Die konkreten Segmente sind Mandanten-
 * Fachdaten (z. B. Branchen-Verticals eines Kunden) und werden im UI bzw.
 * per MCP als Daten gepflegt – nicht als Hardcode ins geteilte Modul.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_segments', function (Blueprint $table) {
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
        Schema::dropIfExists('crm_segments');
    }
};
