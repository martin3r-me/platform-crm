<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comms_contact_enrichment_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crm_contact_id')->constrained('crm_contacts')->cascadeOnDelete();
            $table->string('type', 50)->index();
            $table->text('summary');
            $table->json('details')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comms_contact_enrichment_logs');
    }
};
