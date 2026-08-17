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
        Schema::create('web_dev_task_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->unique()->constrained('tasks')->cascadeOnDelete();
            $table->string('client_project')->nullable();
            $table->string('website_or_page')->nullable();
            $table->string('dev_task_type')->default('frontend');
            $table->text('development_requirement')->nullable();
            $table->string('dev_status')->default('requirement_analysis');
            $table->string('qa_testing_status')->default('untested');
            $table->unsignedInteger('bug_count')->default(0);
            $table->text('staging_url')->nullable();
            $table->text('live_url')->nullable();
            $table->text('qa_feedback_notes')->nullable();
            $table->timestamps();

            $table->index(['dev_status', 'qa_testing_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('web_dev_task_details');
    }
};
