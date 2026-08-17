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
        Schema::create('social_media_task_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->unique()->constrained('tasks')->cascadeOnDelete();
            $table->string('client_brand')->nullable();
            $table->json('platforms')->nullable();
            $table->string('content_type')->nullable();
            $table->text('caption')->nullable();
            $table->text('creative_requirement')->nullable();
            $table->text('hashtags_keywords')->nullable();
            $table->dateTime('publishing_datetime')->nullable();
            $table->foreignId('assigned_designer_id')->nullable()->constrained('employments')->nullOnDelete();
            $table->foreignId('assigned_video_editor_id')->nullable()->constrained('employments')->nullOnDelete();
            $table->string('workflow_stage')->default('idea');
            $table->text('published_link')->nullable();
            $table->timestamps();

            $table->index('workflow_stage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_media_task_details');
    }
};
