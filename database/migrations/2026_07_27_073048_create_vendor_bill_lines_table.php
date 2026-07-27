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
        Schema::create('vendor_bill_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_bill_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_order_line_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('original_vendor_bill_line_id')->nullable()->constrained('vendor_bill_lines')->restrictOnDelete();
            $table->foreignId('item_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('unit_of_measure_id')->nullable()->constrained('units_of_measure')->restrictOnDelete();
            $table->foreignId('tax_code_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('clearing_account_id')->nullable()->constrained('accounts')->restrictOnDelete();
            $table->foreignId('variance_account_id')->nullable()->constrained('accounts')->restrictOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('project_site_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('cost_center_id')->nullable()->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('line_number');
            $table->string('item_code_snapshot')->nullable();
            $table->string('item_name_snapshot');
            $table->string('uom_snapshot')->nullable();
            $table->string('tax_code_snapshot')->nullable();
            $table->decimal('tax_rate_snapshot', 9, 4)->default(0);
            $table->string('tax_calculation_method_snapshot', 30)->nullable();
            $table->decimal('quantity', 19, 4);
            $table->decimal('unit_rate', 19, 4);
            $table->decimal('line_subtotal', 19, 4);
            $table->decimal('tax_amount', 19, 4)->default(0);
            $table->decimal('line_total', 19, 4);
            $table->decimal('receipt_value', 19, 4)->default(0);
            $table->decimal('price_variance', 19, 4)->default(0);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['vendor_bill_id', 'line_number']);
            $table->index(['company_id', 'purchase_order_line_id']);
            $table->index(
                ['company_id', 'project_id', 'clearing_account_id'],
                'vendor_bill_lines_project_clearing_index',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_bill_lines');
    }
};
