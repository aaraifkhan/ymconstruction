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
        Schema::create('intercompany_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('counterparty_company_id')->constrained('companies')->restrictOnDelete();
            $table->uuid('idempotency_key');
            $table->date('transaction_date');
            $table->string('direction');
            $table->decimal('amount', 19, 4);
            $table->foreignId('origin_offset_account_id')->constrained('accounts')->restrictOnDelete();
            $table->foreignId('counterparty_offset_account_id')->constrained('accounts')->restrictOnDelete();
            $table->string('reference', 120)->nullable();
            $table->text('description');
            $table->string('status')->default('draft');
            $table->foreignId('prepared_by_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('origin_approved_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('origin_approved_at')->nullable();
            $table->foreignId('counterparty_approved_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('counterparty_approved_at')->nullable();
            $table->foreignId('rejected_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('posted_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('origin_journal_entry_id')->nullable()->constrained('journal_entries')->restrictOnDelete();
            $table->foreignId('counterparty_journal_entry_id')->nullable()->constrained('journal_entries')->restrictOnDelete();
            $table->foreignId('origin_reversal_entry_id')->nullable()->constrained('journal_entries')->restrictOnDelete();
            $table->foreignId('counterparty_reversal_entry_id')->nullable()->constrained('journal_entries')->restrictOnDelete();
            $table->foreignId('reversed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reversed_at')->nullable();
            $table->text('reversal_reason')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'idempotency_key']);
            $table->index(['company_id', 'status', 'transaction_date'], 'intercompany_origin_status_date_idx');
            $table->index(['counterparty_company_id', 'status', 'transaction_date'], 'intercompany_counterparty_status_date_idx');
            $table->index(['company_id', 'counterparty_company_id', 'transaction_date'], 'intercompany_pair_date_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('intercompany_transactions');
    }
};
