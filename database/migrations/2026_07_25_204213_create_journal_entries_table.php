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
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('financial_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('financial_period_id')->constrained()->restrictOnDelete();
            $table->string('voucher_type');
            $table->string('voucher_number', 80)->nullable();
            $table->uuid('idempotency_key')->nullable();
            $table->string('status')->default('draft');
            $table->date('transaction_date');
            $table->string('reference', 120)->nullable();
            $table->text('description');
            $table->char('currency_code', 3)->default('PKR');
            $table->nullableMorphs('source');
            $table->foreignId('prepared_by_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('submitted_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('approved_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('rejected_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('posted_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('reverses_entry_id')->nullable()->unique()->constrained('journal_entries')->restrictOnDelete();
            $table->foreignId('reversed_by_entry_id')->nullable()->unique()->constrained('journal_entries')->restrictOnDelete();
            $table->decimal('debit_total', 19, 4)->default(0);
            $table->decimal('credit_total', 19, 4)->default(0);
            $table->timestamps();

            $table->unique(
                ['company_id', 'financial_year_id', 'voucher_number'],
                'journal_entries_company_year_voucher_unique',
            );
            $table->unique(['company_id', 'idempotency_key']);
            $table->index(['company_id', 'status', 'transaction_date']);
            $table->index(['company_id', 'financial_period_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};
