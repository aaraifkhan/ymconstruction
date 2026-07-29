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
        Schema::create('hr_data_migration_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hr_data_migration_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('source_row_number');
            $table->string('source_key');
            $table->string('row_checksum', 64);
            $table->json('safe_row_data');
            $table->json('resolved_references')->nullable();
            $table->json('validation_errors')->nullable();
            $table->string('imported_record_type')->nullable();
            $table->unsignedBigInteger('imported_record_id')->nullable();
            $table->string('imported_record_checksum', 64)->nullable();
            $table->timestamps();

            $table->unique(['hr_data_migration_id', 'source_row_number']);
            $table->unique(['hr_data_migration_id', 'source_key']);
            $table->index(['company_id', 'imported_record_type', 'imported_record_id'], 'hr_migration_rows_imported_record_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_data_migration_rows');
    }
};
