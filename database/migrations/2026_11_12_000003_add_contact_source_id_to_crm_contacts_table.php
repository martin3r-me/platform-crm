<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Verknüpft einen Kontakt optional mit einer Kontakt-Quelle (Lookup).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_contacts', function (Blueprint $table) {
            $table->foreignId('contact_source_id')
                ->nullable()
                ->after('language_id')
                ->constrained('crm_contact_sources')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('crm_contacts', function (Blueprint $table) {
            $table->dropForeign(['contact_source_id']);
            $table->dropColumn('contact_source_id');
        });
    }
};
