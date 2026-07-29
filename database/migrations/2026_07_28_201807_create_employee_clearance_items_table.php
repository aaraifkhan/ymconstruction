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
        Schema::create('employee_clearance_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_clearance_id')->constrained()->cascadeOnDelete();
            $table->foreignId('clearance_checklist_template_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source_kind');
            $table->string('source_key');
            $table->string('area');
            $table->string('name');
            $table->boolean('is_mandatory')->default(true);
            $table->string('status')->default('pending');
            $table->text('obligation_snapshot')->nullable();
            $table->text('decision_notes')->nullable();
            $table->text('recovery_recommendation_amount')->nullable();
            $table->text('recovery_recommendation_notes')->nullable();
            $table->foreignId('decided_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->unique(['employee_clearance_id', 'source_key']);
            $table->index(['company_id', 'area', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_clearance_items');
    }
};
