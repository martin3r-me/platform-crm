<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_account_potentials', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 36)->unique();
            $table->foreignId('company_id')->constrained('crm_companies')->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->decimal('target_revenue', 15, 2)->nullable();
            $table->decimal('additional_potential', 15, 2)->nullable();
            $table->decimal('strategic_potential', 15, 2)->nullable();
            $table->string('confidence')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'year']);
            $table->index(['team_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_account_potentials');
    }
};
