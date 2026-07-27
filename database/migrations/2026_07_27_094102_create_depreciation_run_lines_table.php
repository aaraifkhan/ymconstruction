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
        Schema::create('depreciation_run_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('depreciation_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fixed_asset_id')->constrained()->restrictOnDelete();
            $table->foreignId('expense_account_id')->constrained('accounts')->restrictOnDelete();
            $table->foreignId('accumulated_depreciation_account_id')
                ->constrained(
                    table: 'accounts',
                    indexName: 'depreciation_run_lines_accumulated_account_foreign',
                )
                ->restrictOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('project_site_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('cost_center_id')->nullable()->constrained()->restrictOnDelete();
            $table->decimal('opening_accumulated_depreciation', 19, 4);
            $table->decimal('depreciation_amount', 19, 4);
            $table->decimal('closing_accumulated_depreciation', 19, 4);
            $table->decimal('closing_carrying_amount', 19, 4);
            $table->timestamps();

            $table->unique(['depreciation_run_id', 'fixed_asset_id']);
            $table->index(['company_id', 'fixed_asset_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('depreciation_run_lines');
    }
};
