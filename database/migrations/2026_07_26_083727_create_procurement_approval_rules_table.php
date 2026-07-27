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
        Schema::create('procurement_approval_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('document_type', 40);
            $table->unsignedSmallInteger('step_number');
            $table->string('name');
            $table->decimal('minimum_amount', 19, 4)->nullable();
            $table->decimal('maximum_amount', 19, 4)->nullable();
            $table->string('permission_name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'document_type', 'step_number'], 'procurement_rules_company_type_step_unique');
            $table->index(['company_id', 'document_type', 'is_active'], 'procurement_rules_lookup_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procurement_approval_rules');
    }
};
