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
        Schema::create('final_settlement_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('final_settlement_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('line_number');
            $table->string('component_type');
            $table->string('nature');
            $table->foreignId('employee_financing_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('employee_clearance_item_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('account_id')->constrained()->restrictOnDelete();
            $table->string('description');
            $table->text('quantity');
            $table->text('rate');
            $table->text('amount');
            $table->string('source_reference');
            $table->text('evidence_snapshot');
            $table->string('source_checksum');
            $table->string('idempotency_key');
            $table->timestamps();

            $table->unique(['final_settlement_id', 'line_number']);
            $table->unique(['final_settlement_id', 'idempotency_key']);
            $table->index(['company_id', 'component_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('final_settlement_lines');
    }
};
