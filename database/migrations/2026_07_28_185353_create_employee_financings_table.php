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
        Schema::create('employee_financings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employment_id')->constrained()->restrictOnDelete();
            $table->string('reference_number')->nullable();
            $table->string('type');
            $table->string('status')->default('draft');
            $table->date('request_date');
            $table->text('purpose');
            $table->decimal('principal_amount', 19, 4);
            $table->decimal('finance_charge', 19, 4)->default(0);
            $table->decimal('total_repayable', 19, 4);
            $table->unsignedInteger('installment_count');
            $table->date('first_due_date');
            $table->string('installment_frequency')->default('monthly');
            $table->string('currency_code', 3)->default('PKR');
            $table->text('notes')->nullable();
            $table->foreignId('requested_by_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('submitted_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('approved_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('rejected_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('cancelled_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('disbursed_at')->nullable();
            $table->timestamp('settled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'reference_number']);
            $table->index(['company_id', 'status', 'request_date']);
            $table->index(['company_id', 'employment_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_financings');
    }
};
