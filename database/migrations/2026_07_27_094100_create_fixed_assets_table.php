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
        Schema::create('fixed_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_category_id')->constrained()->restrictOnDelete();
            $table->foreignId('vendor_bill_line_id')->nullable()->unique()->constrained()->restrictOnDelete();
            $table->foreignId('capitalization_credit_account_id')->nullable()->constrained('accounts')->restrictOnDelete();
            $table->foreignId('custodian_employment_id')->nullable()->constrained('employments')->restrictOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('project_site_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('cost_center_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('asset_number', 80);
            $table->string('name');
            $table->string('serial_number')->nullable();
            $table->string('location')->nullable();
            $table->string('acquisition_source')->default('manual');
            $table->date('acquired_on');
            $table->date('available_for_use_on');
            $table->decimal('acquisition_cost', 19, 4);
            $table->decimal('residual_value', 19, 4)->default(0);
            $table->unsignedInteger('useful_life_months')->nullable();
            $table->string('depreciation_method')->default('straight_line');
            $table->decimal('accumulated_depreciation', 19, 4)->default(0);
            $table->string('status')->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('prepared_by_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('submitted_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('approved_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('rejected_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('capitalized_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('capitalized_at')->nullable();
            $table->foreignId('acquisition_journal_entry_id')->nullable()->unique()->constrained('journal_entries')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'asset_number']);
            $table->index(['company_id', 'status', 'available_for_use_on']);
            $table->index(['company_id', 'asset_category_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fixed_assets');
    }
};
