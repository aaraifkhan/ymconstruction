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
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_site_id')->constrained()->restrictOnDelete();
            $table->foreignId('counterparty_site_id')->nullable()->constrained('project_sites')->restrictOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->foreignId('goods_receipt_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('inventory_transaction_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('movement_type', 40);
            $table->string('direction', 10);
            $table->decimal('quantity', 19, 4);
            $table->decimal('unit_cost', 19, 4);
            $table->decimal('movement_value', 19, 4);
            $table->decimal('quantity_after', 19, 4);
            $table->decimal('inventory_value_after', 19, 4);
            $table->decimal('average_unit_cost_after', 19, 4);
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['company_id', 'project_site_id', 'item_id', 'occurred_at'], 'inventory_movements_stock_ledger_index');
            $table->index(['goods_receipt_id', 'item_id']);
            $table->index(['inventory_transaction_id', 'item_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
