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
        Schema::create('crm_companies', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            
            // Unternehmens-Felder
            $table->string('name');
            $table->string('legal_name')->nullable(); // Rechtlicher Name (z.B. "Muster GmbH")
            $table->string('trading_name')->nullable(); // Handelsname (z.B. "Muster Solutions")
            $table->string('registration_number')->nullable(); // Handelsregisternummer
            $table->string('tax_number')->nullable(); // Steuernummer
            $table->string('vat_number')->nullable(); // USt-IdNr.
            $table->string('website')->nullable();
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            
            // Lookup-Referenzen
            $table->foreignId('industry_id')->nullable()->constrained('crm_industries')->onDelete('set null');
            $table->foreignId('legal_form_id')->nullable()->constrained('crm_legal_forms')->onDelete('set null');
            $table->foreignId('contact_status_id')->constrained('crm_contact_statuses')->onDelete('restrict');
            $table->foreignId('country_id')->nullable()->constrained('crm_countries')->onDelete('set null');
            
            // User/Team-Kontext
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('owned_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('team_id')->constrained('teams')->onDelete('cascade');
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Indexe für Performance
            $table->index(['team_id', 'is_active']);
            $table->index(['created_by_user_id', 'owned_by_user_id']);
            $table->index(['name', 'legal_name']);
            $table->index('registration_number');
            $table->index('tax_number');
            $table->index('vat_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crm_companies');
    }
}; 