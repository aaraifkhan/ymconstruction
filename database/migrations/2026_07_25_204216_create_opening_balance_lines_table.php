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
        Schema::create('opening_balance_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('opening_balance_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('line_number');
            $table->foreignId('account_id')->constrained()->restrictOnDelete();
            $table->text('description')->nullable();
            $table->decimal('debit', 19, 4)->default(0);
            $table->decimal('credit', 19, 4)->default(0);
            $table->foreignId('party_id')->nullable()->constrained('parties')->restrictOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->restrictOnDelete();
            $table->foreignId('cost_center_id')->nullable()->constrained('cost_centers')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['opening_balance_batch_id', 'line_number']);
            $table->index(['company_id', 'account_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('opening_balance_lines');
    }
};
