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
        Schema::create('petty_cash_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->date('reconciliation_date');
            $table->decimal('system_expected_balance', 19, 4);
            $table->decimal('on_account_held', 19, 4)->default(0);
            $table->decimal('physical_counted_cash', 19, 4)->default(0);
            $table->decimal('difference', 19, 4)->default(0);
            $table->text('explanation')->nullable();
            $table->string('status', 30)->default('reconciled');
            $table->foreignId('reconciled_by_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('reviewed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'reconciliation_date'], 'pcr_company_date_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('petty_cash_reconciliations');
    }
};
