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
        Schema::create('attendance_import_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attendance_device_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source');
            $table->string('status')->default('pending');
            $table->string('original_filename')->nullable();
            $table->string('stored_file_path')->nullable();
            $table->string('batch_checksum', 64);
            $table->text('cursor_before')->nullable();
            $table->text('cursor_after')->nullable();
            $table->json('source_metadata')->nullable();
            $table->unsignedInteger('row_count')->default(0);
            $table->unsignedInteger('accepted_count')->default(0);
            $table->unsignedInteger('duplicate_count')->default(0);
            $table->unsignedInteger('quarantined_count')->default(0);
            $table->unsignedInteger('error_count')->default(0);
            $table->foreignId('initiated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('failure_summary')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'source', 'batch_checksum'], 'attendance_batch_idempotency_unique');
            $table->index(['company_id', 'status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_import_batches');
    }
};
