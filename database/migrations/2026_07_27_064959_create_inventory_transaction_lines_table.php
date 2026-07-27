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
        Schema::create('inventory_transaction_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_transaction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('line_number');
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->foreignId('unit_of_measure_id')->constrained('units_of_measure')->restrictOnDelete();
            $table->foreignId('goods_receipt_line_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('offset_account_id')->nullable()->constrained('accounts')->restrictOnDelete();
            $table->string('item_code_snapshot');
            $table->string('item_name_snapshot');
            $table->string('uom_snapshot');
            $table->decimal('quantity', 19, 4);
            $table->decimal('unit_cost_snapshot', 19, 4)->default(0);
            $table->decimal('line_value', 19, 4)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['inventory_transaction_id', 'line_number'], 'inventory_transaction_line_number_unique');
            $table->index(['company_id', 'item_id']);
            $table->index('goods_receipt_line_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_transaction_lines');
    }
};
