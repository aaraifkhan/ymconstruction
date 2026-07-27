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
        Schema::create('purchase_requisition_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_requisition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('line_number');
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->foreignId('unit_of_measure_id')->constrained('units_of_measure')->restrictOnDelete();
            $table->foreignId('project_budget_line_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('item_code_snapshot');
            $table->string('item_name_snapshot');
            $table->string('uom_snapshot', 40);
            $table->decimal('quantity', 19, 4);
            $table->decimal('estimated_rate', 19, 4);
            $table->decimal('estimated_amount', 19, 4);
            $table->decimal('ordered_quantity', 19, 4)->default(0);
            $table->text('specification')->nullable();
            $table->timestamps();

            $table->unique(['purchase_requisition_id', 'line_number'], 'purchase_requisition_lines_number_unique');
            $table->index(['company_id', 'item_id']);
            $table->index(['project_budget_line_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_requisition_lines');
    }
};
