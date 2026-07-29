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
        Schema::create('employee_financing_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_financing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_financing_installment_id')->nullable()
                ->constrained()->nullOnDelete();
            $table->foreignId('treasury_transaction_id')->nullable()
                ->constrained()->nullOnDelete();
            $table->foreignId('payroll_entry_id')->nullable()
                ->constrained()->nullOnDelete();
            $table->foreignId('reversal_of_id')->nullable()
                ->constrained('employee_financing_transactions')->nullOnDelete();
            $table->string('type');
            $table->date('effective_date');
            $table->decimal('principal_amount', 19, 4)->default(0);
            $table->decimal('finance_charge_amount', 19, 4)->default(0);
            $table->decimal('total_amount', 19, 4);
            $table->string('idempotency_key');
            $table->text('reason')->nullable();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'idempotency_key']);
            $table->index(['company_id', 'employee_financing_id', 'effective_date']);
            $table->index(['treasury_transaction_id', 'type']);
            $table->index(['payroll_entry_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_financing_transactions');
    }
};
