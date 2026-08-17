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
        Schema::create('daily_report_task_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_work_report_id')->constrained('daily_work_reports')->cascadeOnDelete();
            $table->foreignId('task_id')->nullable()->constrained('tasks')->nullOnDelete();
            $table->string('task_title');
            $table->string('status_today')->default('in_progress');
            $table->decimal('hours_spent', 4, 2)->default(0);
            $table->unsignedTinyInteger('progress_percentage')->default(0);
            $table->text('deliverable_summary')->nullable();
            $table->text('work_links')->nullable();
            $table->text('blockers')->nullable();
            $table->timestamps();

            $table->index(['daily_work_report_id', 'task_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_report_task_items');
    }
};
