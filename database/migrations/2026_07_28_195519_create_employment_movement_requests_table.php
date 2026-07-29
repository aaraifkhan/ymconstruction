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
        Schema::create('employment_movement_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employment_id')->constrained()->restrictOnDelete();
            $table->string('reference_number')->nullable();
            $table->string('type');
            $table->string('status')->default('draft');
            $table->date('effective_on');
            $table->foreignId('target_department_id')->nullable()->constrained('departments')->restrictOnDelete();
            $table->foreignId('target_designation_id')->nullable()->constrained('designations')->restrictOnDelete();
            $table->foreignId('target_reporting_employment_id')->nullable()->constrained('employments')->restrictOnDelete();
            $table->foreignId('target_work_location_id')->nullable()->constrained('work_locations')->restrictOnDelete();
            $table->string('target_employment_category')->nullable();
            $table->foreignId('employment_compensation_id')->nullable()->constrained('employment_compensation')->restrictOnDelete();
            $table->text('reason');
            $table->text('before_snapshot')->nullable();
            $table->text('target_snapshot')->nullable();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('submitted_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('approved_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('applied_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('applied_at')->nullable();
            $table->foreignId('rejected_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['company_id', 'reference_number']);
            $table->index(['company_id', 'status', 'effective_on']);
            $table->index(['employment_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employment_movement_requests');
    }
};
