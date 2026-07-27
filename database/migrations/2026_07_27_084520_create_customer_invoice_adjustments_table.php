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
        Schema::create('customer_invoice_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tax_code_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('type');
            $table->string('description');
            $table->decimal('amount', 19, 4);
            $table->timestamps();

            $table->unique(['customer_invoice_id', 'type']);
            $table->index(['company_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_invoice_adjustments');
    }
};
