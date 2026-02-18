<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('crm_phone_numbers', function (Blueprint $table) {
            // WhatsApp status: unknown, available, unavailable, opted_in
            $table->string('whatsapp_status', 20)->default('unknown')->after('verified_at');

            // Index for filtering by WhatsApp availability
            $table->index('whatsapp_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('crm_phone_numbers', function (Blueprint $table) {
            $table->dropIndex(['whatsapp_status']);
            $table->dropColumn('whatsapp_status');
        });
    }
};
