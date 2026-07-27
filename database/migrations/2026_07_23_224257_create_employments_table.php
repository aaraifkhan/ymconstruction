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
        Schema::create('employments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->string('employee_code', 100);
            $table->date('joining_date');
            $table->date('ending_date')->nullable();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('designation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reporting_to_employment_id')->nullable()->constrained('employments')->nullOnDelete();
            $table->string('employment_category');
            $table->string('employment_status');
            $table->time('work_start_time')->nullable();
            $table->time('work_end_time')->nullable();
            $table->unsignedTinyInteger('working_days_per_week')->default(6);
            $table->foreignId('interviewed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('documents_verified_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('documents_verified_at')->nullable();
            $table->boolean('appointment_letter_issued')->default(false);
            $table->text('hr_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'employee_id']);
            $table->unique(['company_id', 'employee_code']);
            $table->index(['company_id', 'employment_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employments');
    }
};
