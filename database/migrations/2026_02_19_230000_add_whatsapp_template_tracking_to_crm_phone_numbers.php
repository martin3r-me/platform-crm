<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_phone_numbers', function (Blueprint $table) {
            $table->unsignedTinyInteger('whatsapp_template_attempts')->default(0)->after('whatsapp_status');
            $table->timestamp('whatsapp_template_last_sent_at')->nullable()->after('whatsapp_template_attempts');
        });
    }

    public function down(): void
    {
        Schema::table('crm_phone_numbers', function (Blueprint $table) {
            $table->dropColumn(['whatsapp_template_attempts', 'whatsapp_template_last_sent_at']);
        });
    }
};
