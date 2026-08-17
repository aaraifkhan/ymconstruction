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
        Schema::create('video_task_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->unique()->constrained('tasks')->cascadeOnDelete();
            $table->string('video_project_name')->nullable();
            $table->string('client_brand')->nullable();
            $table->string('video_type')->nullable();
            $table->unsignedInteger('target_duration_seconds')->nullable();
            $table->text('raw_footage_url')->nullable();
            $table->text('script_text')->nullable();
            $table->text('voiceover_url')->nullable();
            $table->text('reference_video_url')->nullable();
            $table->text('editing_instructions')->nullable();
            $table->text('draft_video_url')->nullable();
            $table->text('final_video_url')->nullable();
            $table->text('published_link')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_task_details');
    }
};
