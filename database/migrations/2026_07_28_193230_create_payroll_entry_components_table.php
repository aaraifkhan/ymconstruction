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
        Schema::create('payroll_entry_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payroll_entry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employment_id')->constrained()->restrictOnDelete();
            $table->string('type');
            $table->string('nature');
            $table->nullableMorphs('source');
            $table->decimal('quantity', 19, 4)->default(1);
            $table->text('rate')->nullable();
            $table->text('amount');
            $table->string('account_component')->nullable();
            $table->string('source_checksum', 64);
            $table->text('evidence_snapshot');
            $table->string('idempotency_key');
            $table->timestamps();
            $table->unique(['company_id', 'idempotency_key']);
            $table->index(['company_id', 'employment_id', 'type'], 'payroll_entry_components_employment_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_entry_components');
    }
};
