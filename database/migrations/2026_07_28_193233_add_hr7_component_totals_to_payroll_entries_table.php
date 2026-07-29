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
        Schema::table('payroll_entries', function (Blueprint $table) {
            $table->text('bonus_amount')->nullable()->after('other_allowance');
            $table->text('incentive_amount')->nullable()->after('bonus_amount');
            $table->text('unpaid_leave_deduction')->nullable()->after('absence_deduction');
            $table->text('late_deduction')->nullable()->after('unpaid_leave_deduction');
            $table->text('half_day_deduction')->nullable()->after('late_deduction');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_entries', function (Blueprint $table) {
            $table->dropColumn([
                'bonus_amount', 'incentive_amount', 'unpaid_leave_deduction',
                'late_deduction', 'half_day_deduction',
            ]);
        });
    }
};
