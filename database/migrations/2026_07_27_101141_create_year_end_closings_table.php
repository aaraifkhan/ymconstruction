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
        Schema::create('year_end_closings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('financial_year_id')->constrained()->restrictOnDelete();
            $table->uuid('idempotency_key');
            $table->string('status')->default('draft');
            $table->decimal('profit_or_loss', 19, 4)->default(0);
            $table->char('calculation_checksum', 64)->nullable();
            $table->json('calculation_snapshot')->nullable();
            $table->foreignId('retained_earnings_account_id')->constrained('accounts')->restrictOnDelete();
            $table->foreignId('prepared_by_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('posted_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->restrictOnDelete();
            $table->foreignId('reversed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reversed_at')->nullable();
            $table->text('reversal_reason')->nullable();
            $table->foreignId('reversal_entry_id')->nullable()->constrained('journal_entries')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'financial_year_id']);
            $table->unique(['company_id', 'idempotency_key']);
            $table->index(['company_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('year_end_closings');
    }
};
