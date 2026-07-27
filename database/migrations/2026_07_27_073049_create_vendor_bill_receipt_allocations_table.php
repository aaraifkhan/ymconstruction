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
        Schema::create('vendor_bill_receipt_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_bill_line_id')->constrained()->cascadeOnDelete();
            $table->foreignId('goods_receipt_line_id')->constrained()->restrictOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 19, 4);
            $table->decimal('receipt_unit_cost', 19, 4);
            $table->decimal('receipt_value', 19, 4);
            $table->timestamps();

            $table->unique(['vendor_bill_line_id', 'goods_receipt_line_id'], 'vendor_bill_receipt_line_unique');
            $table->index(['company_id', 'goods_receipt_line_id'], 'vendor_bill_receipt_consumption_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_bill_receipt_allocations');
    }
};
