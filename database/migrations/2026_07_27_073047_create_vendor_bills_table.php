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
        Schema::create('vendor_bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_order_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('original_vendor_bill_id')->nullable()->constrained('vendor_bills')->restrictOnDelete();
            $table->foreignId('vendor_id')->constrained('parties')->restrictOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('project_site_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('vendor_bill_number', 40)->nullable();
            $table->string('vendor_invoice_number', 100);
            $table->string('type', 30);
            $table->date('invoice_date');
            $table->date('due_date');
            $table->string('status', 30)->index();
            $table->string('match_status', 30)->nullable()->index();
            $table->char('currency_code', 3)->default('PKR');
            $table->decimal('subtotal', 19, 4)->default(0);
            $table->decimal('tax_total', 19, 4)->default(0);
            $table->decimal('gross_total', 19, 4)->default(0);
            $table->decimal('deduction_total', 19, 4)->default(0);
            $table->decimal('net_payable', 19, 4)->default(0);
            $table->json('match_snapshot')->nullable();
            $table->string('match_snapshot_hash', 64)->nullable();
            $table->text('mismatch_reason')->nullable();
            $table->foreignId('mismatch_overridden_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('mismatch_overridden_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('prepared_by_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('submitted_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('reviewed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('approved_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('rejected_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('posted_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('journal_entry_id')->nullable()->unique()->constrained()->restrictOnDelete();
            $table->foreignId('reversal_journal_entry_id')->nullable()->unique()->constrained('journal_entries')->restrictOnDelete();
            $table->foreignId('reversed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reversed_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'vendor_bill_number']);
            $table->unique(['company_id', 'vendor_id', 'vendor_invoice_number'], 'vendor_bills_company_vendor_invoice_unique');
            $table->index(['company_id', 'vendor_id', 'status', 'due_date'], 'vendor_bills_ap_aging_index');
            $table->index(['company_id', 'purchase_order_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_bills');
    }
};
