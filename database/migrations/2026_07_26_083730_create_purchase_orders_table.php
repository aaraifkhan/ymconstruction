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
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_requisition_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('vendor_id')->constrained('parties')->restrictOnDelete();
            $table->foreignId('project_id')->constrained()->restrictOnDelete();
            $table->foreignId('project_site_id')->constrained()->restrictOnDelete();
            $table->string('purchase_order_number')->nullable();
            $table->date('order_date');
            $table->string('status', 40)->default('draft');
            $table->unsignedSmallInteger('approval_round')->default(0);
            $table->string('currency_code', 3)->default('PKR');
            $table->unsignedSmallInteger('payment_terms_days')->default(0);
            $table->text('payment_terms')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('subtotal', 19, 4)->default(0);
            $table->decimal('tax_total', 19, 4)->default(0);
            $table->decimal('grand_total', 19, 4)->default(0);
            $table->json('approved_snapshot')->nullable();
            $table->string('approved_snapshot_hash', 64)->nullable();
            $table->foreignId('prepared_by_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('submitted_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('approved_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('rejected_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('ordered_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('ordered_at')->nullable();
            $table->foreignId('cancelled_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'purchase_order_number']);
            $table->index(['company_id', 'status', 'order_date']);
            $table->index(['company_id', 'vendor_id']);
            $table->index(['purchase_requisition_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
