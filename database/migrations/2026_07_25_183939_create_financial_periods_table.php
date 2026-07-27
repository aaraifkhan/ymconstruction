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
        Schema::create('financial_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('financial_year_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('period_number');
            $table->string('name', 50);
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('status')->default('open');
            $table->foreignId('closed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('locked_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('locked_at')->nullable();
            $table->foreignId('reopened_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reopened_at')->nullable();
            $table->text('reopen_reason')->nullable();
            $table->timestamps();

            $table->unique(['financial_year_id', 'period_number']);
            $table->unique(['company_id', 'starts_on', 'ends_on']);
            $table->index(['company_id', 'status', 'starts_on']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_periods');
    }
};
