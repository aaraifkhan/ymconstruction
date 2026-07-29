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
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained()->restrictOnDelete();
            $table->foreignId('leave_policy_id')->nullable()->constrained()->nullOnDelete();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->decimal('requested_units', 10, 2);
            $table->text('reason');
            $table->string('status')->default('draft');
            $table->boolean('is_paid_snapshot');
            $table->string('payroll_impact_snapshot');
            $table->foreignId('requested_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('requested_at')->nullable();
            $table->foreignId('manager_decided_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('manager_decided_at')->nullable();
            $table->foreignId('hr_decided_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('hr_decided_at')->nullable();
            $table->text('decision_reason')->nullable();
            $table->foreignId('cancelled_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['company_id', 'employment_id', 'starts_on', 'ends_on']);
            $table->index(['company_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};
