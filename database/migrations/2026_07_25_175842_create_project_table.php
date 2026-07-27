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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_party_id')->constrained('parties')->restrictOnDelete();
            $table->foreignId('consultant_party_id')->nullable()->constrained('parties')->nullOnDelete();
            $table->string('code', 50);
            $table->string('name');
            $table->text('location')->nullable();
            $table->date('planned_start_date')->nullable();
            $table->date('planned_completion_date')->nullable();
            $table->date('actual_start_date')->nullable();
            $table->date('actual_completion_date')->nullable();
            $table->decimal('contract_value', 19, 4)->default(0);
            $table->json('retention_terms')->nullable();
            $table->json('mobilization_terms')->nullable();
            $table->string('currency_code', 3)->default('PKR');
            $table->string('status');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'code']);
            $table->index(['company_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
