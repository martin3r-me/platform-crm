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
        Schema::create('crm_contact_relations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            
            // Beziehungs-Referenzen
            $table->foreignId('contact_id')->constrained('crm_contacts')->onDelete('cascade');
            $table->foreignId('company_id')->constrained('crm_companies')->onDelete('cascade');
            $table->foreignId('relation_type_id')->constrained('crm_contact_relation_types')->onDelete('restrict');
            
            // Beziehungs-Details
            $table->string('position')->nullable(); // "CEO", "Manager", "Abteilungsleiter"
            $table->text('notes')->nullable(); // Zusätzliche Notizen zur Beziehung
            
            // Zeitraum
            $table->date('start_date')->nullable(); // Seit wann
            $table->date('end_date')->nullable(); // Bis wann (null = aktiv)
            
            // Status
            $table->boolean('is_primary')->default(false); // Hauptkontakt für das Unternehmen
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Indexe für Performance
            $table->index(['contact_id', 'is_active']);
            $table->index(['company_id', 'is_active']);
            $table->index(['relation_type_id', 'is_active']);
            $table->index(['is_primary', 'company_id']);
            $table->index(['start_date', 'end_date']);
            
            // Unique Constraint: Ein Kontakt kann nur einmal pro Unternehmen einen bestimmten Typ haben
            $table->unique(['contact_id', 'company_id', 'relation_type_id'], 'unique_contact_company_relation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crm_contact_relations');
    }
}; 