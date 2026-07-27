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
        Schema::create('financial_years', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name', 50);
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('status')->default('open');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'name']);
            $table->unique(['company_id', 'starts_on', 'ends_on']);
            $table->index(['company_id', 'status', 'starts_on']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_years');
    }
};
