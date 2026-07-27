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
        Schema::create('asset_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('code', 50);
            $table->string('name');
            $table->foreignId('cost_account_id')->constrained('accounts')->restrictOnDelete();
            $table->foreignId('accumulated_depreciation_account_id')->nullable()->constrained('accounts')->restrictOnDelete();
            $table->foreignId('depreciation_expense_account_id')->nullable()->constrained('accounts')->restrictOnDelete();
            $table->foreignId('disposal_gain_account_id')->nullable()->constrained('accounts')->restrictOnDelete();
            $table->foreignId('disposal_loss_account_id')->nullable()->constrained('accounts')->restrictOnDelete();
            $table->unsignedInteger('default_useful_life_months')->nullable();
            $table->string('depreciation_method')->default('straight_line');
            $table->boolean('is_depreciable')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'code']);
            $table->index(['company_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_categories');
    }
};
