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
        Schema::create('customer_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('parties')->restrictOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('project_site_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('original_customer_invoice_id')->nullable()->constrained('customer_invoices')->restrictOnDelete();
            $table->string('invoice_number')->nullable();
            $table->string('customer_reference')->nullable();
            $table->string('type');
            $table->string('category');
            $table->date('invoice_date');
            $table->date('due_date');
            $table->string('certificate_number')->nullable();
            $table->date('certificate_date')->nullable();
            $table->decimal('contract_value_snapshot', 19, 4)->default(0);
            $table->decimal('previous_certified_amount', 19, 4)->default(0);
            $table->decimal('work_value', 19, 4)->default(0);
            $table->decimal('variation_amount', 19, 4)->default(0);
            $table->decimal('subtotal', 19, 4)->default(0);
            $table->decimal('tax_total', 19, 4)->default(0);
            $table->decimal('gross_total', 19, 4)->default(0);
            $table->decimal('retention_amount', 19, 4)->default(0);
            $table->decimal('wht_amount', 19, 4)->default(0);
            $table->decimal('mobilization_recovery_amount', 19, 4)->default(0);
            $table->decimal('receivable_amount', 19, 4)->default(0);
            $table->string('currency_code', 3)->default('PKR');
            $table->string('status')->default('draft');
            $table->text('description')->nullable();
            $table->json('commercial_snapshot')->nullable();
            $table->string('commercial_snapshot_hash', 64)->nullable();
            $table->foreignId('prepared_by_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('submitted_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('approved_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('rejected_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('posted_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('journal_entry_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('reversal_journal_entry_id')->nullable()->constrained('journal_entries')->restrictOnDelete();
            $table->foreignId('reversed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reversed_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'invoice_number']);
            $table->unique(['company_id', 'customer_id', 'customer_reference'], 'customer_reference_unique');
            $table->unique('journal_entry_id');
            $table->unique('reversal_journal_entry_id');
            $table->index(['company_id', 'status', 'invoice_date']);
            $table->index(['company_id', 'customer_id', 'status']);
            $table->index(['company_id', 'project_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_invoices');
    }
};
