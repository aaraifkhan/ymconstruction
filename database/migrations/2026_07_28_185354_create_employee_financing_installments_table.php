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
        Schema::create('employee_financing_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_financing_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('schedule_version')->default(1);
            $table->unsignedInteger('installment_number');
            $table->date('due_date');
            $table->decimal('principal_due', 19, 4);
            $table->decimal('finance_charge_due', 19, 4)->default(0);
            $table->decimal('total_due', 19, 4);
            $table->decimal('principal_recovered', 19, 4)->default(0);
            $table->decimal('finance_charge_recovered', 19, 4)->default(0);
            $table->decimal('principal_waived', 19, 4)->default(0);
            $table->decimal('finance_charge_waived', 19, 4)->default(0);
            $table->string('status')->default('pending');
            $table->timestamps();

            $table->unique(
                ['employee_financing_id', 'schedule_version', 'installment_number'],
                'employee_financing_installment_version_unique',
            );
            $table->index(['company_id', 'due_date', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_financing_installments');
    }
};
