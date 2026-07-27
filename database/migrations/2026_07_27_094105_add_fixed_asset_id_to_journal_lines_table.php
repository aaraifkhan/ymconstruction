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
            $table->foreignId('fixed_asset_id')->nullable()->constrained()->restrictOnDelete();
            $table->index(['company_id', 'fixed_asset_id', 'account_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('journal_lines', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'fixed_asset_id', 'account_id']);
            $table->dropConstrainedForeignId('fixed_asset_id');
        });
    }
};
