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
        Schema::create('employee_bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('bank_name');
            $table->string('branch_name')->nullable();
            $table->string('branch_code', 50)->nullable();
            $table->string('account_title');
            $table->text('account_number')->nullable();
            $table->text('iban')->nullable();
            $table->boolean('is_primary_for_payroll')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['employee_id', 'is_primary_for_payroll']);
            $table->index(['employee_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_bank_accounts');
    }
};
