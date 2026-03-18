<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comms_email_inbound_mails', function (Blueprint $table) {
            $table->text('bcc')->nullable()->after('cc');
            $table->string('original_recipient')->nullable()->after('reply_to');
        });
    }

    public function down(): void
    {
        Schema::table('comms_email_inbound_mails', function (Blueprint $table) {
            $table->dropColumn(['bcc', 'original_recipient']);
        });
    }
};
