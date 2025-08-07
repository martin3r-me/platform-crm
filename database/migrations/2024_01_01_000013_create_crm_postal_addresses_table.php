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
        Schema::create('crm_postal_addresses', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            
            // Polymorphe Beziehung
            $table->morphs('addressable');
            
            // Adressfelder
            $table->string('street')->nullable();
            $table->string('house_number')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('city')->nullable();
            $table->text('additional_info')->nullable(); // Zusatzinfo wie "3. Stock"
            
            // Referenzen
            $table->foreignId('country_id')->nullable()->constrained('crm_countries')->onDelete('set null');
            $table->foreignId('state_id')->nullable()->constrained('crm_states')->onDelete('set null');
            $table->foreignId('address_type_id')->constrained('crm_address_types')->onDelete('restrict');
            
            // Status
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Indexe für Performance
            $table->index(['address_type_id', 'is_active']);
            $table->index(['country_id', 'state_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crm_postal_addresses');
    }
}; 