<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comms_email_threads', function (Blueprint $table) {
            // Polymorphic contact relation (CrmContact, Applicant, etc.)
            if (!Schema::hasColumn('comms_email_threads', 'contact_type')) {
                $table->string('contact_type')->nullable()->after('context_model_id');
            }
            if (!Schema::hasColumn('comms_email_threads', 'contact_id')) {
                $table->unsignedBigInteger('contact_id')->nullable()->after('contact_type');
            }
        });

        // Add index if it doesn't exist
        $indexExists = collect(Schema::getIndexes('comms_email_threads'))
            ->contains(fn ($index) => $index['name'] === 'cet_contact_idx');

        if (!$indexExists) {
            Schema::table('comms_email_threads', function (Blueprint $table) {
                $table->index(['contact_type', 'contact_id'], 'cet_contact_idx');
            });
        }
    }

    public function down(): void
    {
        $indexExists = collect(Schema::getIndexes('comms_email_threads'))
            ->contains(fn ($index) => $index['name'] === 'cet_contact_idx');

        if ($indexExists) {
            Schema::table('comms_email_threads', function (Blueprint $table) {
                $table->dropIndex('cet_contact_idx');
            });
        }

        Schema::table('comms_email_threads', function (Blueprint $table) {
            if (Schema::hasColumn('comms_email_threads', 'contact_type')) {
                $table->dropColumn('contact_type');
            }
            if (Schema::hasColumn('comms_email_threads', 'contact_id')) {
                $table->dropColumn('contact_id');
            }
        });
    }
};
