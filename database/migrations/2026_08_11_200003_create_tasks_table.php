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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('task_code');
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_team_id')->nullable()->constrained('department_teams')->nullOnDelete();
            $table->foreignId('assigned_to_employment_id')->constrained('employments')->cascadeOnDelete();
            $table->foreignId('assigned_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('priority')->default('medium');
            $table->string('status')->default('not_started');
            $table->unsignedTinyInteger('progress_percentage')->default(0);
            $table->date('start_date')->nullable();
            $table->date('deadline_date')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('revision_count')->default(0);
            $table->text('blocker_note')->nullable();

            // Level 1 Approval (Team Lead)
            $table->foreignId('lead_reviewed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('lead_reviewed_at')->nullable();
            $table->text('lead_review_notes')->nullable();

            // Level 2 Approval (Department Head)
            $table->foreignId('head_approved_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('head_approved_at')->nullable();
            $table->text('head_approval_notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'task_code']);
            $table->index(['company_id', 'status', 'assigned_to_employment_id']);
            $table->index(['company_id', 'department_team_id', 'status']);
            $table->index(['company_id', 'deadline_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
