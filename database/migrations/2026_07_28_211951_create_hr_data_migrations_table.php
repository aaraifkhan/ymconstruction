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
        Schema::create('hr_data_migrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->string('type', 40);
            $table->uuid('idempotency_key');
            $table->string('source_filename');
            $table->text('source_path')->nullable();
            $table->string('source_checksum', 64);
            $table->string('status', 20)->default('draft');
            $table->unsignedInteger('row_count')->default(0);
            $table->unsignedInteger('valid_row_count')->default(0);
            $table->unsignedInteger('imported_row_count')->default(0);
            $table->json('source_totals')->nullable();
            $table->json('imported_totals')->nullable();
            $table->json('validation_summary')->nullable();
            $table->foreignId('prepared_by_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('validated_by_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('validated_at')->nullable();
            $table->foreignId('imported_by_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('imported_at')->nullable();
            $table->foreignId('rolled_back_by_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('rolled_back_at')->nullable();
            $table->text('rollback_reason')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'type', 'source_checksum']);
            $table->unique(['company_id', 'idempotency_key']);
            $table->index(['company_id', 'status', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_data_migrations');
    }
};
