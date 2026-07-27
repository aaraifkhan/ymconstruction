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
        Schema::table('opening_balance_migrations', function (Blueprint $table) {
            $table->string('source_path')->nullable()->after('source_filename');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('opening_balance_migrations', function (Blueprint $table) {
            $table->dropColumn('source_path');
        });
    }
};
