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
        Schema::create('treasury_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('party_id')->nullable()->constrained('parties')->restrictOnDelete();
            $table->foreignId('employment_id')->nullable()->constrained('employments')->restrictOnDelete();
            $table->foreignId('source_account_id')->nullable()->constrained('accounts')->restrictOnDelete();
            $table->foreignId('destination_account_id')->nullable()->constrained('accounts')->restrictOnDelete();
            $table->foreignId('source_company_bank_account_id')->nullable()->constrained('company_bank_accounts')->restrictOnDelete();
            $table->foreignId('destination_company_bank_account_id')
                ->nullable()
                ->constrained(
                    table: 'company_bank_accounts',
                    indexName: 'treasury_transactions_destination_bank_foreign',
                )
                ->restrictOnDelete();
            $table->string('transaction_number')->nullable();
            $table->string('type');
            $table->string('purpose');
            $table->string('counterparty_type')->nullable();
            $table->date('transaction_date');
            $table->date('value_date')->nullable();
            $table->string('status')->default('draft');
            $table->string('currency_code', 3)->default('PKR');
            $table->decimal('amount', 19, 4);
            $table->decimal('allocated_amount', 19, 4)->default(0);
            $table->decimal('unallocated_amount', 19, 4)->default(0);
            $table->string('instrument_type')->default('electronic');
            $table->string('instrument_number')->nullable();
            $table->date('instrument_date')->nullable();
            $table->string('bank_reference')->nullable();
            $table->string('external_reference')->nullable();
            $table->text('description');
            $table->text('notes')->nullable();
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
            $table->foreignId('journal_entry_id')->nullable()->unique()->constrained('journal_entries')->restrictOnDelete();
            $table->foreignId('reversal_journal_entry_id')->nullable()->unique()->constrained('journal_entries')->restrictOnDelete();
            $table->foreignId('reversed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reversed_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'transaction_number']);
            $table->index(['company_id', 'status', 'transaction_date']);
            $table->index(['company_id', 'party_id', 'status']);
            $table->index(['company_id', 'source_company_bank_account_id', 'transaction_date'], 'treasury_source_bank_date_index');
            $table->index(['company_id', 'destination_company_bank_account_id', 'transaction_date'], 'treasury_destination_bank_date_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('treasury_transactions');
    }
};
