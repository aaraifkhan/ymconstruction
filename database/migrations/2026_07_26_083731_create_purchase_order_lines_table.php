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
        Schema::create('purchase_order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_requisition_line_id')->nullable()->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('line_number');
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->foreignId('unit_of_measure_id')->constrained('units_of_measure')->restrictOnDelete();
            $table->foreignId('tax_code_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('item_code_snapshot');
            $table->string('item_name_snapshot');
            $table->string('uom_snapshot', 40);
            $table->string('tax_code_snapshot')->nullable();
            $table->decimal('tax_rate_snapshot', 9, 4)->default(0);
            $table->string('tax_calculation_method_snapshot', 20)->nullable();
            $table->decimal('quantity', 19, 4);
            $table->decimal('unit_rate', 19, 4);
            $table->decimal('line_subtotal', 19, 4);
            $table->decimal('tax_amount', 19, 4)->default(0);
            $table->decimal('line_total', 19, 4);
            $table->decimal('received_quantity', 19, 4)->default(0);
            $table->decimal('cancelled_quantity', 19, 4)->default(0);
            $table->text('specification')->nullable();
            $table->timestamps();

            $table->unique(['purchase_order_id', 'line_number'], 'purchase_order_lines_number_unique');
            $table->index(['company_id', 'item_id']);
            $table->index(['purchase_requisition_line_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_order_lines');
    }
};
