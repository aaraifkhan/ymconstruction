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
        Schema::table('employments', function (Blueprint $table) {
            $table->string('employment_type')->default('permanent')->after('employment_category');
            $table->date('probation_start_date')->nullable()->after('employment_status');
            $table->date('probation_end_date')->nullable()->after('probation_start_date');
            $table->date('confirmation_date')->nullable()->after('probation_end_date');
            $table->unsignedSmallInteger('notice_period_days')->nullable()->after('confirmation_date');
            $table->foreignId('work_location_id')
                ->nullable()
                ->after('notice_period_days')
                ->constrained()
                ->nullOnDelete();

            $table->index(['company_id', 'employment_type']);
            $table->index(['company_id', 'work_location_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employments', function (Blueprint $table) {
            $table->dropForeign(['work_location_id']);
            $table->dropIndex(['company_id', 'employment_type']);
            $table->dropIndex(['company_id', 'work_location_id']);
            $table->dropColumn([
                'employment_type',
                'probation_start_date',
                'probation_end_date',
                'confirmation_date',
                'notice_period_days',
                'work_location_id',
            ]);
        });
    }
};
