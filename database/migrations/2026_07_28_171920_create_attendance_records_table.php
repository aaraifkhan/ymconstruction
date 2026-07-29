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
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shift_assignment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('attendance_rule_id')->nullable()->constrained()->nullOnDelete();
            $table->date('attendance_date');
            $table->string('day_status');
            $table->string('state')->default('draft');
            $table->dateTime('first_in_at')->nullable();
            $table->dateTime('last_out_at')->nullable();
            $table->unsignedSmallInteger('scheduled_minutes')->default(0);
            $table->unsignedSmallInteger('worked_minutes')->default(0);
            $table->unsignedSmallInteger('late_minutes')->default(0);
            $table->unsignedSmallInteger('overtime_minutes')->default(0);
            $table->string('source_checksum', 64)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('finalized_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'employment_id', 'attendance_date']);
            $table->index(['company_id', 'attendance_date', 'state']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};
