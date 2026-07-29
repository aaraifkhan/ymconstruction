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
        Schema::table('employments', function (Blueprint $table): void {
            $table->index(['company_id', 'joining_date']);
            $table->index(['company_id', 'ending_date']);
            $table->index(['company_id', 'department_id']);
            $table->index(['company_id', 'designation_id']);
        });

        Schema::table('employment_compensation', function (Blueprint $table): void {
            $table->index(['company_id', 'status', 'effective_from']);
        });

        Schema::table('payroll_entries', function (Blueprint $table): void {
            $table->index(['company_id', 'payroll_run_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_entries', function (Blueprint $table): void {
            $table->dropIndex(['company_id', 'payroll_run_id']);
        });

        Schema::table('employment_compensation', function (Blueprint $table): void {
            $table->dropIndex(['company_id', 'status', 'effective_from']);
        });

        Schema::table('employments', function (Blueprint $table): void {
            $table->dropIndex(['company_id', 'joining_date']);
            $table->dropIndex(['company_id', 'ending_date']);
            $table->dropIndex(['company_id', 'department_id']);
            $table->dropIndex(['company_id', 'designation_id']);
        });
    }
};
