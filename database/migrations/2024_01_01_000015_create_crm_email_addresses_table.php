<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('crm_email_addresses', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            
            // Polymorphe Beziehung
            $table->morphs('emailable');
            
            // E-Mail-Felder
            $table->string('email_address');
            $table->text('notes')->nullable(); // Notizen zur E-Mail
            
            // Referenzen
            $table->foreignId('email_type_id')->constrained('crm_email_types')->onDelete('restrict');
            
            // Status
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_verified')->default(false); // E-Mail bestätigt
            $table->timestamp('verified_at')->nullable(); // Zeitpunkt der Bestätigung
            $table->timestamps();
            
            // Indexe für Performance
            $table->index(['email_type_id', 'is_active']);
            $table->index('email_address');
            $table->index('is_verified');
            $table->index('verified_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crm_email_addresses');
    }
}; 