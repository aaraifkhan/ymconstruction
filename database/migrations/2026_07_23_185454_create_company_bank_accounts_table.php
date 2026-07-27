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
        Schema::create('company_bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('bank_name');
            $table->string('branch_name')->nullable();
            $table->string('branch_code', 50)->nullable();
            $table->string('account_title');
            $table->text('account_number')->nullable();
            $table->text('iban')->nullable();
            $table->string('swift_code', 50)->nullable();
            $table->char('currency_code', 3)->default('PKR');
            $table->string('account_type')->default('current');
            $table->boolean('is_default_for_payroll')->default(false)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_bank_accounts');
    }
};
