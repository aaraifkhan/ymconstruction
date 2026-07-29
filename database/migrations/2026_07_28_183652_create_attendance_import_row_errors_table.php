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
        Schema::create('attendance_import_row_errors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attendance_import_batch_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('row_number');
            $table->string('error_code', 100);
            $table->string('external_reference')->nullable();
            $table->text('message');
            $table->json('safe_row_data')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'attendance_import_batch_id', 'row_number'], 'attendance_import_row_error_lookup_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_import_row_errors');
    }
};
