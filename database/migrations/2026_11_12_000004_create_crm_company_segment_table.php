<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pivot: Unternehmen <-> Segmente (Mehrfachzuordnung).
 *
 * Ein Unternehmen kann mehreren Segmenten/Verticals zugeordnet sein
 * (z. B. Hotel + Fine Dining + Banqueting).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_company_segment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('crm_companies')->cascadeOnDelete();
            $table->foreignId('segment_id')->constrained('crm_segments')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'segment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_company_segment');
    }
};
