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
        Schema::table('treasury_transactions', function (Blueprint $table) {
            $table->foreignId('employee_financing_id')->nullable()
                ->after('employment_id')
                ->constrained()
                ->restrictOnDelete();
            $table->index(
                ['company_id', 'employee_financing_id', 'status'],
                'treasury_tx_company_financing_status_index',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('treasury_transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('employee_financing_id');
        });
    }
};
