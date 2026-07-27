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
        Schema::create('tax_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('code', 50);
            $table->string('name');
            $table->string('type');
            $table->decimal('rate', 9, 4);
            $table->string('calculation_method');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_recoverable')->default(false);
            $table->boolean('is_active')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'code', 'effective_from']);
            $table->index(['company_id', 'type', 'is_active']);
            $table->index(['company_id', 'code', 'effective_from', 'effective_to']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_codes');
    }
};
