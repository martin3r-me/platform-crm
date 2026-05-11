<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comms_newsletter_recipients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('newsletter_id')->index();
            $table->unsignedBigInteger('contact_id')->nullable();
            $table->string('email_address');
            $table->string('status', 20)->default('pending')->index();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->timestamp('bounced_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->unique(['newsletter_id', 'email_address']);
            $table->foreign('newsletter_id')->references('id')->on('comms_newsletters')->cascadeOnDelete();
            $table->foreign('contact_id')->references('id')->on('crm_contacts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comms_newsletter_recipients');
    }
};
