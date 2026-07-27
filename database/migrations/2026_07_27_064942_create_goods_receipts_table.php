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
        Schema::create('goods_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_order_id')->constrained()->restrictOnDelete();
            $table->foreignId('vendor_id')->constrained('parties')->restrictOnDelete();
            $table->foreignId('project_id')->constrained()->restrictOnDelete();
            $table->foreignId('project_site_id')->constrained()->restrictOnDelete();
            $table->string('goods_receipt_number')->nullable();
            $table->string('delivery_reference')->nullable();
            $table->date('delivery_date');
            $table->string('status', 30)->default('draft');
            $table->text('receiving_notes')->nullable();
            $table->foreignId('received_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('received_at')->nullable();
            $table->foreignId('inspected_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('inspected_at')->nullable();
            $table->text('inspection_notes')->nullable();
            $table->foreignId('handed_over_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('handed_over_at')->nullable();
            $table->foreignId('inventory_journal_entry_id')->nullable()->unique()->constrained('journal_entries')->restrictOnDelete();
            $table->decimal('accepted_value', 19, 4)->default(0);
            $table->timestamps();

            $table->unique(['company_id', 'goods_receipt_number']);
            $table->index(['company_id', 'status', 'delivery_date']);
            $table->index(['purchase_order_id', 'status']);
            $table->index(['company_id', 'project_site_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goods_receipts');
    }
};
