<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Fachliche Rolle einer Company (Partner / Großhändler / Endkunde …).
     * Eigene Achse neben contact_status_id: der Status beschreibt die
     * Beziehung (wandert aufs Vertriebsboard), der Typ die Stammdaten-Rolle.
     */
    public function up(): void
    {
        Schema::table('crm_companies', function (Blueprint $table) {
            $table->foreignId('company_type_id')
                ->nullable()
                ->after('contact_status_id')
                ->constrained('crm_company_types')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('crm_companies', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_type_id');
        });
    }
};
