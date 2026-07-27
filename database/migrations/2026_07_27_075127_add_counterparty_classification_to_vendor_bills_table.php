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
        Schema::table('vendor_bills', function (Blueprint $table) {
            $table->string('counterparty_classification', 30)->nullable()->after('vendor_id')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendor_bills', function (Blueprint $table) {
            $table->dropColumn('counterparty_classification');
        });
    }
};
