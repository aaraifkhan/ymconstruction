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
        Schema::create('performance_appraisal_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('performance_appraisal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('performance_kpi_id')->constrained()->restrictOnDelete();
            $table->text('goal');
            $table->decimal('weight', 9, 4);
            $table->text('score')->nullable();
            $table->text('reviewer_comments')->nullable();
            $table->timestamps();
            $table->unique(
                ['performance_appraisal_id', 'performance_kpi_id'],
                'appraisal_item_appraisal_kpi_unique',
            );
            $table->index(['company_id', 'performance_kpi_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('performance_appraisal_items');
    }
};
