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
        Schema::table('attendance_monthly_summaries', function (Blueprint $table) {
            $table->unsignedInteger('scheduled_minutes')->default(0)->after('scheduled_days');
            $table->decimal('unpaid_leave_days', 9, 4)->default(0)->after('unpaid_leave_units');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_monthly_summaries', function (Blueprint $table) {
            $table->dropColumn(['scheduled_minutes', 'unpaid_leave_days']);
        });
    }
};
