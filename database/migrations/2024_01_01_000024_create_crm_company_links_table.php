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
        Schema::create('crm_company_links', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained('crm_companies')->cascadeOnDelete();
            $table->string('linkable_type');
            $table->unsignedBigInteger('linkable_id');
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            // Indexes
            $table->index(['linkable_type', 'linkable_id']);
            $table->index(['company_id', 'linkable_type', 'linkable_id']);
            $table->index(['team_id']);
            $table->index(['created_by_user_id']);

            // Unique constraint
            $table->unique(['company_id', 'linkable_type', 'linkable_id'], 'crm_company_links_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crm_company_links');
    }
};
