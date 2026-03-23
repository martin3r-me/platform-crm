<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comms_whatsapp_messages', function (Blueprint $table) {
            $table->json('reactions')->nullable()->after('meta_payload');
        });
    }

    public function down(): void
    {
        Schema::table('comms_whatsapp_messages', function (Blueprint $table) {
            $table->dropColumn('reactions');
        });
    }
};
