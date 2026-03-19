<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_contacts', function (Blueprint $table) {
            $table->boolean('is_blacklisted')->default(false)->after('is_active');
            $table->index(['team_id', 'is_blacklisted']);
        });
    }

    public function down(): void
    {
        Schema::table('crm_contacts', function (Blueprint $table) {
            $table->dropIndex(['team_id', 'is_blacklisted']);
            $table->dropColumn('is_blacklisted');
        });
    }
};
