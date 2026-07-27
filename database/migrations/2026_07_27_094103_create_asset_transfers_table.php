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
        Schema::create('asset_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fixed_asset_id')->constrained()->restrictOnDelete();
            $table->foreignId('from_custodian_employment_id')->nullable()->constrained('employments')->restrictOnDelete();
            $table->foreignId('to_custodian_employment_id')->nullable()->constrained('employments')->restrictOnDelete();
            $table->foreignId('from_project_id')->nullable()->constrained('projects')->restrictOnDelete();
            $table->foreignId('to_project_id')->nullable()->constrained('projects')->restrictOnDelete();
            $table->foreignId('from_project_site_id')->nullable()->constrained('project_sites')->restrictOnDelete();
            $table->foreignId('to_project_site_id')->nullable()->constrained('project_sites')->restrictOnDelete();
            $table->foreignId('from_cost_center_id')->nullable()->constrained('cost_centers')->restrictOnDelete();
            $table->foreignId('to_cost_center_id')->nullable()->constrained('cost_centers')->restrictOnDelete();
            $table->string('from_location')->nullable();
            $table->string('to_location')->nullable();
            $table->date('effective_on');
            $table->text('reason');
            $table->foreignId('transferred_by_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'fixed_asset_id', 'effective_on']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_transfers');
    }
};
