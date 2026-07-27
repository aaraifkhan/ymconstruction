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
        Schema::create('goods_receipt_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_receipt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_order_line_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('line_number');
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->foreignId('unit_of_measure_id')->constrained('units_of_measure')->restrictOnDelete();
            $table->string('item_code_snapshot');
            $table->string('item_name_snapshot');
            $table->string('uom_snapshot');
            $table->decimal('received_quantity', 19, 4);
            $table->decimal('accepted_quantity', 19, 4)->default(0);
            $table->decimal('rejected_quantity', 19, 4)->default(0);
            $table->decimal('rejected_returned_quantity', 19, 4)->default(0);
            $table->decimal('accepted_returned_quantity', 19, 4)->default(0);
            $table->decimal('unit_cost_snapshot', 19, 4);
            $table->decimal('accepted_value', 19, 4)->default(0);
            $table->string('inspection_result', 30)->nullable();
            $table->text('inspection_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->unique(['goods_receipt_id', 'line_number']);
            $table->unique(['goods_receipt_id', 'purchase_order_line_id'], 'goods_receipt_po_line_unique');
            $table->index(['company_id', 'item_id']);
            $table->index(
                ['purchase_order_line_id', 'inspection_result'],
                'goods_receipt_lines_po_inspection_index',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_lines');
    }
};
