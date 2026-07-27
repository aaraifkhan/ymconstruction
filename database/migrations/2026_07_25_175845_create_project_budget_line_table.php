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
        Schema::create('project_budget_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_budget_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cost_center_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('item_category_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('cost_code', 50);
            $table->string('description');
            $table->decimal('amount', 19, 4);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['project_budget_id', 'cost_code']);
            $table->index(['company_id', 'cost_center_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_budget_lines');
    }
};
