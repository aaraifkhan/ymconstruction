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
        Schema::create('accounting_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('profile')->default('generic');
            $table->char('base_currency_code', 3)->default('PKR');
            $table->string('timezone')->default('Asia/Karachi');
            $table->unsignedTinyInteger('fiscal_year_start_month')->default(7);
            $table->unsignedTinyInteger('fiscal_year_start_day')->default(1);
            $table->unsignedTinyInteger('monetary_precision')->default(4);
            $table->unsignedTinyInteger('display_precision')->default(2);
            $table->string('inventory_valuation_method')->default('moving_weighted_average');
            $table->boolean('allow_negative_inventory')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounting_settings');
    }
};
