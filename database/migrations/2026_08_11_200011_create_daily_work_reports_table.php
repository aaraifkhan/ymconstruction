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
        Schema::create('daily_work_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employment_id')->constrained('employments')->cascadeOnDelete();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_team_id')->nullable()->constrained('department_teams')->nullOnDelete();
            $table->date('report_date');
            $table->timestamp('submitted_at')->nullable();
            $table->string('submission_status')->default('on_time');

            $table->unsignedInteger('tasks_completed_count')->default(0);
            $table->unsignedInteger('tasks_in_progress_count')->default(0);
            $table->unsignedInteger('tasks_pending_count')->default(0);
            $table->unsignedTinyInteger('overall_progress_percentage')->default(0);
            $table->unsignedInteger('deliverables_count')->default(0);
            $table->text('blockers_summary')->nullable();
            $table->text('additional_comments')->nullable();

            $table->string('review_status')->default('pending');
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['employment_id', 'report_date']);
            $table->index(['company_id', 'report_date', 'submission_status']);
            $table->index(['department_team_id', 'report_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_work_reports');
    }
};
