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
            $table->foreignId('journal_entry_id')->nullable()->unique()->constrained()->restrictOnDelete();
            $table->foreignId('reversal_journal_entry_id')->nullable()->unique()->constrained('journal_entries')->restrictOnDelete();
            $table->foreignId('posted_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('reversed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reversed_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_runs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reversed_by_id');
            $table->dropConstrainedForeignId('posted_by_id');
            $table->dropConstrainedForeignId('reversal_journal_entry_id');
            $table->dropConstrainedForeignId('journal_entry_id');
            $table->dropColumn(['posted_at', 'reversed_at']);
        });
    }
};
