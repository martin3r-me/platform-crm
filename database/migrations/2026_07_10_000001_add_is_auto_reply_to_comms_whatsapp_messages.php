<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * is_auto_reply = automatische Sofort-Quittung (z.B. OOO-Abwesenheitsnotiz,
     * Sprachnachricht-Hinweis), die NICHT als menschliche Antwort zählt.
     * Ausgewertet von Recruiting (ConversationInboxService, "verpasst"-Zähler).
     */
    public function up(): void
    {
        Schema::table('comms_whatsapp_messages', function (Blueprint $table) {
            $table->boolean('is_auto_reply')->default(false)->after('template_params');
        });
    }

    public function down(): void
    {
        Schema::table('comms_whatsapp_messages', function (Blueprint $table) {
            $table->dropColumn('is_auto_reply');
        });
    }
};
