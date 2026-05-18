<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_contact_lists', function (Blueprint $table) {
            $table->boolean('requires_doi')->default(false)->after('is_active');
            $table->string('doi_confirmation_subject', 255)->nullable()->after('requires_doi');
            $table->text('doi_confirmation_body')->nullable()->after('doi_confirmation_subject');
        });
    }

    public function down(): void
    {
        Schema::table('crm_contact_lists', function (Blueprint $table) {
            $table->dropColumn(['requires_doi', 'doi_confirmation_subject', 'doi_confirmation_body']);
        });
    }
};
