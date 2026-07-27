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
        Schema::create('opening_balance_migrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('financial_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('financial_period_id')->constrained()->restrictOnDelete();
            $table->date('opening_date');
            $table->uuid('idempotency_key');
            $table->string('source_filename');
            $table->char('source_checksum', 64);
            $table->string('status')->default('draft');
            $table->unsignedInteger('row_count')->default(0);
            $table->unsignedInteger('valid_row_count')->default(0);
            $table->decimal('source_debit_total', 19, 4)->default(0);
            $table->decimal('source_credit_total', 19, 4)->default(0);
            $table->json('validation_summary')->nullable();
            $table->foreignId('prepared_by_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('validated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validated_at')->nullable();
            $table->foreignId('imported_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('imported_at')->nullable();
            $table->foreignId('opening_balance_batch_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('reversed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reversed_at')->nullable();
            $table->text('reversal_reason')->nullable();
            $table->foreignId('reversal_entry_id')->nullable()->constrained('journal_entries')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'idempotency_key']);
            $table->unique(['company_id', 'source_checksum', 'opening_date'], 'opening_migration_source_unique');
            $table->index(['company_id', 'status', 'opening_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('opening_balance_migrations');
    }
};
