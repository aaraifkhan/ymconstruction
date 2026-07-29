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
        Schema::create('payroll_calculation_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('requires_finalized_attendance')->default(false);
            $table->boolean('prorate_allowances')->default(false);
            $table->decimal('absence_day_factor', 9, 4)->nullable();
            $table->decimal('unpaid_leave_day_factor', 9, 4)->nullable();
            $table->decimal('half_day_factor', 9, 4)->nullable();
            $table->boolean('deduct_late_minutes')->default(false);
            $table->unsignedSmallInteger('standard_day_minutes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['company_id', 'effective_from']);
            $table->index(['company_id', 'is_active', 'effective_from', 'effective_to'], 'payroll_calculation_rules_effective_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_calculation_rules');
    }
};
