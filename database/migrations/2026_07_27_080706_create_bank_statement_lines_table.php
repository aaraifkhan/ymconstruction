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
        Schema::create('bank_statement_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_statement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_bank_account_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('line_number');
            $table->date('transaction_date');
            $table->date('value_date')->nullable();
            $table->text('description');
            $table->string('bank_reference')->nullable();
            $table->decimal('debit', 19, 4)->default(0);
            $table->decimal('credit', 19, 4)->default(0);
            $table->decimal('balance', 19, 4);
            $table->string('fingerprint', 64);
            $table->timestamps();

            $table->unique(['bank_statement_id', 'line_number']);
            $table->unique(['bank_statement_id', 'fingerprint']);
            $table->index(['company_id', 'company_bank_account_id', 'transaction_date'], 'bank_statement_line_lookup_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_statement_lines');
    }
};
