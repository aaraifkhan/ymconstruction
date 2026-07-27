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
        Schema::create('bank_reconciliation_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_reconciliation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bank_statement_line_id')->constrained()->restrictOnDelete();
            $table->foreignId('journal_line_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 19, 4);
            $table->foreignId('matched_by_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('matched_at');
            $table->timestamps();

            $table->unique(
                ['bank_reconciliation_id', 'bank_statement_line_id', 'journal_line_id'],
                'bank_reconciliation_match_unique',
            );
            $table->index(
                ['company_id', 'bank_statement_line_id'],
                'bank_reconciliation_matches_statement_index',
            );
            $table->index(['company_id', 'journal_line_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_reconciliation_matches');
    }
};
