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
        Schema::create('payroll_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employment_id')->constrained()->restrictOnDelete();
            $table->foreignId('employment_compensation_id')->nullable()->constrained('employment_compensation')->nullOnDelete();
            $table->string('employee_name');
            $table->string('employee_code', 100);
            $table->string('designation')->nullable();
            $table->string('employment_category');
            $table->unsignedSmallInteger('period_days');
            $table->decimal('payable_days', 6, 2);
            $table->text('basic_salary');
            $table->text('payable_basic');
            $table->text('house_travel_allowance');
            $table->text('food_allowance');
            $table->text('other_allowance');
            $table->text('gross_salary');
            $table->text('absence_deduction');
            $table->text('loan_advance_deduction');
            $table->text('other_deduction');
            $table->text('net_salary');
            $table->text('bank_amount');
            $table->text('cash_amount');
            $table->string('currency_code', 3)->default('PKR');
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['payroll_run_id', 'employment_id']);
            $table->index(['company_id', 'employment_category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_entries');
    }
};
