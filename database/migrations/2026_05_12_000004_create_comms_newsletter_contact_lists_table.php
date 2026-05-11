<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comms_newsletter_contact_lists', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('newsletter_id');
            $table->unsignedBigInteger('contact_list_id');
            $table->timestamps();

            $table->foreign('newsletter_id')->references('id')->on('comms_newsletters')->cascadeOnDelete();
            $table->foreign('contact_list_id')->references('id')->on('crm_contact_lists')->cascadeOnDelete();
            $table->unique(['newsletter_id', 'contact_list_id'], 'newsletter_contact_list_unique');
        });

        // Migrate existing data: copy contact_list_id to pivot table
        $newsletters = \Illuminate\Support\Facades\DB::table('comms_newsletters')
            ->whereNotNull('contact_list_id')
            ->select(['id', 'contact_list_id'])
            ->get();

        foreach ($newsletters as $nl) {
            \Illuminate\Support\Facades\DB::table('comms_newsletter_contact_lists')->insert([
                'newsletter_id' => $nl->id,
                'contact_list_id' => $nl->contact_list_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Drop old column
        Schema::table('comms_newsletters', function (Blueprint $table) {
            $table->dropForeign(['contact_list_id']);
            $table->dropColumn('contact_list_id');
        });
    }

    public function down(): void
    {
        Schema::table('comms_newsletters', function (Blueprint $table) {
            $table->unsignedBigInteger('contact_list_id')->nullable()->after('comms_channel_id');
            $table->foreign('contact_list_id')->references('id')->on('crm_contact_lists')->nullOnDelete();
        });

        // Migrate data back: take first contact list per newsletter
        $pivots = \Illuminate\Support\Facades\DB::table('comms_newsletter_contact_lists')
            ->select(['newsletter_id', 'contact_list_id'])
            ->orderBy('id')
            ->get()
            ->unique('newsletter_id');

        foreach ($pivots as $pivot) {
            \Illuminate\Support\Facades\DB::table('comms_newsletters')
                ->where('id', $pivot->newsletter_id)
                ->update(['contact_list_id' => $pivot->contact_list_id]);
        }

        Schema::dropIfExists('comms_newsletter_contact_lists');
    }
};
