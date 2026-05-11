<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comms_unsubscribes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_id')->index();
            $table->string('email_address');
            $table->unsignedBigInteger('contact_id')->nullable();
            $table->string('reason')->nullable();
            $table->timestamp('unsubscribed_at')->useCurrent();
            $table->timestamps();

            $table->unique(['team_id', 'email_address']);
            $table->foreign('team_id')->references('id')->on('teams')->cascadeOnDelete();
            $table->foreign('contact_id')->references('id')->on('crm_contacts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comms_unsubscribes');
    }
};
