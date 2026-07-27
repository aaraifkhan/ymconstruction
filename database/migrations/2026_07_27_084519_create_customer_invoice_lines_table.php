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
        Schema::create('customer_invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('unit_of_measure_id')->nullable()->constrained('units_of_measure')->restrictOnDelete();
            $table->foreignId('revenue_account_id')->constrained('accounts')->restrictOnDelete();
            $table->foreignId('cogs_account_id')->nullable()->constrained('accounts')->restrictOnDelete();
            $table->foreignId('inventory_site_id')->nullable()->constrained('project_sites')->restrictOnDelete();
            $table->foreignId('tax_code_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('original_customer_invoice_line_id')->nullable()->constrained('customer_invoice_lines')->restrictOnDelete();
            $table->unsignedInteger('line_number');
            $table->string('item_code_snapshot')->nullable();
            $table->string('item_name_snapshot');
            $table->string('uom_snapshot')->nullable();
            $table->text('description')->nullable();
            $table->decimal('quantity', 19, 4);
            $table->decimal('unit_rate', 19, 4);
            $table->decimal('line_subtotal', 19, 4)->default(0);
            $table->decimal('tax_rate_snapshot', 9, 4)->default(0);
            $table->string('tax_method_snapshot')->nullable();
            $table->decimal('tax_amount', 19, 4)->default(0);
            $table->decimal('line_total', 19, 4)->default(0);
            $table->decimal('cogs_unit_cost', 19, 4)->default(0);
            $table->decimal('cogs_amount', 19, 4)->default(0);
            $table->timestamps();

            $table->unique(['customer_invoice_id', 'line_number']);
            $table->index(['company_id', 'item_id']);
            $table->index('original_customer_invoice_line_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_invoice_lines');
    }
};
