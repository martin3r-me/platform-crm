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
        Schema::create('crm_phone_numbers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            
            // Polymorphe Beziehung
            $table->morphs('phoneable');
            
            // Telefonnummer-Felder
            $table->string('raw_input')->nullable();        // z. B. "0151 1234567"
            $table->string('international')->nullable();    // z. B. "+49 151 1234567"
            $table->string('national')->nullable();         // z. B. "0151 1234567"
            $table->string('country_code', 8)->nullable();  // z. B. "+49"
            $table->string('extension')->nullable(); // Durchwahl
            $table->text('notes')->nullable(); // Notizen zur Nummer
            
            // Referenzen
            $table->foreignId('phone_type_id')->constrained('crm_phone_types')->onDelete('restrict');
            
            // Status
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            
            // Indexe für Performance
            $table->index(['phone_type_id', 'is_active']);
            $table->index('international');
            $table->index('national');
            $table->index('country_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crm_phone_numbers');
    }
}; 