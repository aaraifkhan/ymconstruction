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
        Schema::create('opening_balance_migration_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('opening_balance_migration_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('source_row_number');
            $table->string('account_code', 50);
            $table->string('party_code', 80)->nullable();
            $table->string('project_code', 80)->nullable();
            $table->string('cost_center_code', 80)->nullable();
            $table->text('description')->nullable();
            $table->decimal('debit', 19, 4)->default(0);
            $table->decimal('credit', 19, 4)->default(0);
            $table->foreignId('account_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('party_id')->nullable()->constrained('parties')->restrictOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('cost_center_id')->nullable()->constrained()->restrictOnDelete();
            $table->json('validation_errors')->nullable();
            $table->timestamps();

            $table->unique(['opening_balance_migration_id', 'source_row_number'], 'opening_migration_row_unique');
            $table->index(['company_id', 'account_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('opening_balance_migration_rows');
    }
};
