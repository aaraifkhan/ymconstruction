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
        Schema::create('treasury_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('treasury_transaction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('allocatable_type');
            $table->unsignedBigInteger('allocatable_id');
            $table->string('allocation_type');
            $table->decimal('amount', 19, 4);
            $table->string('reference_snapshot')->nullable();
            $table->date('due_date_snapshot')->nullable();
            $table->timestamps();

            $table->unique(
                ['treasury_transaction_id', 'allocatable_type', 'allocatable_id'],
                'treasury_allocation_source_unique',
            );
            $table->index(['allocatable_type', 'allocatable_id']);
            $table->index(['company_id', 'allocation_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('treasury_allocations');
    }
};
