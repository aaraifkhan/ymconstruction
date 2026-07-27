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
        Schema::create('bank_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_bank_account_id')->constrained()->restrictOnDelete();
            $table->foreignId('bank_statement_id')->constrained()->restrictOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->string('status')->default('draft');
            $table->decimal('statement_closing_balance', 19, 4)->default(0);
            $table->decimal('book_closing_balance', 19, 4)->default(0);
            $table->decimal('difference', 19, 4)->default(0);
            $table->foreignId('prepared_by_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('closed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('reopened_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reopened_at')->nullable();
            $table->text('reopen_reason')->nullable();
            $table->timestamps();

            $table->unique('bank_statement_id');
            $table->index(['company_id', 'company_bank_account_id', 'status'], 'bank_reconciliation_lookup_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_reconciliations');
    }
};
