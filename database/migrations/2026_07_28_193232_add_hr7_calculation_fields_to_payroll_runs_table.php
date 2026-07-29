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
        Schema::table('payroll_runs', function (Blueprint $table) {
            $table->foreignId('payroll_calculation_rule_id')->nullable()->after('currency_code')
                ->constrained()->restrictOnDelete();
            $table->unsignedInteger('generation_revision')->default(0)->after('payroll_calculation_rule_id');
            $table->string('source_checksum', 64)->nullable()->after('generation_revision');
            $table->foreignId('generated_by_id')->nullable()->after('source_checksum')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('generated_at')->nullable()->after('generated_by_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_runs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payroll_calculation_rule_id');
            $table->dropConstrainedForeignId('generated_by_id');
            $table->dropColumn(['generation_revision', 'source_checksum', 'generated_at']);
        });
    }
};
