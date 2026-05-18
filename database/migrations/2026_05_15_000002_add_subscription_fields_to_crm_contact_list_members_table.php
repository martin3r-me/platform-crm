<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_contact_list_members', function (Blueprint $table) {
            $table->string('status', 20)->default('subscribed')->after('notes');
            $table->timestamp('subscribed_at')->nullable()->after('status');
            $table->timestamp('unsubscribed_at')->nullable()->after('subscribed_at');
            $table->string('consent_source', 100)->nullable()->after('unsubscribed_at');
            $table->timestamp('opt_in_confirmed_at')->nullable()->after('consent_source');
            $table->string('doi_token', 64)->nullable()->after('opt_in_confirmed_at');

            $table->index('doi_token');
            $table->index(['status', 'contact_list_id']);
        });

        // Backfill existing rows
        DB::table('crm_contact_list_members')
            ->whereNull('subscribed_at')
            ->update([
                'status' => 'subscribed',
                'subscribed_at' => DB::raw('created_at'),
                'consent_source' => 'legacy_import',
            ]);
    }

    public function down(): void
    {
        Schema::table('crm_contact_list_members', function (Blueprint $table) {
            $table->dropIndex(['doi_token']);
            $table->dropIndex(['status', 'contact_list_id']);
            $table->dropColumn([
                'status',
                'subscribed_at',
                'unsubscribed_at',
                'consent_source',
                'opt_in_confirmed_at',
                'doi_token',
            ]);
        });
    }
};
