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
        Schema::create('design_task_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->unique()->constrained('tasks')->cascadeOnDelete();
            $table->string('design_type')->nullable();
            $table->string('dimensions')->nullable();
            $table->string('target_platform')->nullable();
            $table->string('brand_client')->nullable();
            $table->text('reference_links')->nullable();
            $table->text('copy_content')->nullable();
            $table->string('source_file_path')->nullable();
            $table->string('preview_file_path')->nullable();
            $table->string('final_file_path')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('design_task_details');
    }
};
