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
        Schema::create('crm_contacts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            
            // Kontakt-Felder
            $table->string('first_name');
            $table->string('last_name');
            $table->string('middle_name')->nullable();
            $table->string('nickname')->nullable();
            $table->date('birth_date')->nullable();
            $table->text('notes')->nullable();
            
            // Lookup-Referenzen
            $table->foreignId('salutation_id')->nullable()->constrained('crm_salutations')->onDelete('set null');
            $table->foreignId('academic_title_id')->nullable()->constrained('crm_academic_titles')->onDelete('set null');
            $table->foreignId('gender_id')->nullable()->constrained('crm_genders')->onDelete('set null');
            $table->foreignId('language_id')->nullable()->constrained('crm_languages')->onDelete('set null');
            $table->foreignId('contact_status_id')->nullable()->default(1)->constrained('crm_contact_statuses')->onDelete('restrict');
            
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
            $table->index(['first_name', 'last_name']);
            $table->index('birth_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crm_contacts');
    }
}; 