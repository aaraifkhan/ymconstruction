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
        Schema::table('journal_lines', function (Blueprint $table) {
            $table->foreignId('related_company_id')->nullable()->after('company_id')->constrained('companies')->restrictOnDelete();
            $table->index(['company_id', 'related_company_id', 'account_id'], 'journal_lines_related_company_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('journal_lines', function (Blueprint $table) {
            $table->dropIndex('journal_lines_related_company_idx');
            $table->dropConstrainedForeignId('related_company_id');
        });
    }
};
