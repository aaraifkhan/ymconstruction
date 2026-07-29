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
        Schema::create('attendance_raw_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attendance_device_id')->constrained()->restrictOnDelete();
            $table->foreignId('attendance_import_batch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('attendance_device_user_mapping_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('employment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('external_user_id', 191);
            $table->string('original_punched_at_local', 50);
            $table->string('timezone', 100);
            $table->dateTime('punched_at_utc');
            $table->string('direction')->nullable();
            $table->string('source_event_id', 191)->nullable();
            $table->json('safe_payload')->nullable();
            $table->string('event_fingerprint', 64);
            $table->string('processing_status')->default('pending');
            $table->text('processing_error')->nullable();
            $table->timestamp('received_at');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->unique(
                ['company_id', 'attendance_device_id', 'event_fingerprint'],
                'attendance_raw_event_fingerprint_unique',
            );
            $table->unique(
                ['company_id', 'attendance_device_id', 'source_event_id'],
                'attendance_raw_event_source_unique',
            );
            $table->index(
                ['company_id', 'processing_status', 'received_at'],
                'attendance_raw_event_processing_index',
            );
            $table->index(
                ['company_id', 'external_user_id', 'punched_at_utc'],
                'attendance_raw_event_user_time_index',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_raw_events');
    }
};
