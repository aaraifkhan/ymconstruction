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
        Schema::create('bank_statements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_bank_account_id')->constrained()->restrictOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('opening_balance', 19, 4);
            $table->decimal('closing_balance', 19, 4);
            $table->string('currency_code', 3)->default('PKR');
            $table->string('status')->default('draft');
            $table->string('source_file_name')->nullable();
            $table->string('source_sha256', 64)->nullable();
            $table->string('source_storage_path')->nullable();
            $table->foreignId('imported_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('imported_at')->nullable();
            $table->foreignId('locked_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('locked_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['company_id', 'company_bank_account_id', 'period_start', 'period_end'],
                'bank_statement_period_unique',
            );
            $table->index(['company_id', 'status', 'period_end']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_statements');
    }
};
