<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comms_newsletter_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('newsletter_id');
            $table->string('filename');
            $table->string('mime')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->string('disk')->default('emails');
            $table->string('path');
            $table->timestamps();

            $table->foreign('newsletter_id')
                ->references('id')
                ->on('comms_newsletters')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comms_newsletter_attachments');
    }
};
