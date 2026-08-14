<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registergericht (Amtsgericht) fürs Impressum. FLYNK verlangt es als
 * legal_register_court; bislang gab es in crm_companies nur die Registernummer.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_companies', function (Blueprint $table) {
            $table->string('register_court')->nullable()->after('registration_number');
        });
    }

    public function down(): void
    {
        Schema::table('crm_companies', function (Blueprint $table) {
            $table->dropColumn('register_court');
        });
    }
};
