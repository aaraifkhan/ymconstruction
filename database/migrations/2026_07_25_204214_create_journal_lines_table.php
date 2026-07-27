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
        Schema::create('journal_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('line_number');
            $table->foreignId('account_id')->constrained()->restrictOnDelete();
            $table->string('account_code_snapshot', 50);
            $table->string('account_name_snapshot');
            $table->text('description')->nullable();
            $table->decimal('debit', 19, 4)->default(0);
            $table->decimal('credit', 19, 4)->default(0);
            $table->foreignId('party_id')->nullable()->constrained('parties')->restrictOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->restrictOnDelete();
            $table->foreignId('project_site_id')->nullable()->constrained('project_sites')->restrictOnDelete();
            $table->foreignId('cost_center_id')->nullable()->constrained('cost_centers')->restrictOnDelete();
            $table->foreignId('employment_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('company_bank_account_id')->nullable()->constrained()->restrictOnDelete();
            $table->timestamps();

            $table->unique(['journal_entry_id', 'line_number']);
            $table->index(['company_id', 'account_id', 'journal_entry_id']);
            $table->index(['company_id', 'project_id', 'account_id']);
            $table->index(['company_id', 'party_id', 'account_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_lines');
    }
};
