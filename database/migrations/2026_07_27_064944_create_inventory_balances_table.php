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
        Schema::create('inventory_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_site_id')->constrained()->restrictOnDelete();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity_on_hand', 19, 4)->default(0);
            $table->decimal('inventory_value', 19, 4)->default(0);
            $table->decimal('average_unit_cost', 19, 4)->default(0);
            $table->timestamps();

            $table->unique(['company_id', 'project_site_id', 'item_id'], 'inventory_balance_company_site_item_unique');
            $table->index(['company_id', 'item_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_balances');
    }
};
