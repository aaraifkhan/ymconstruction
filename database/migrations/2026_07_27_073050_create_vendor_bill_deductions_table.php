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
        Schema::create('vendor_bill_deductions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_bill_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tax_code_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('account_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('type', 30);
            $table->string('description');
            $table->decimal('rate_snapshot', 9, 4)->default(0);
            $table->decimal('amount', 19, 4);
            $table->timestamps();

            $table->index(['company_id', 'type', 'account_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_bill_deductions');
    }
};
