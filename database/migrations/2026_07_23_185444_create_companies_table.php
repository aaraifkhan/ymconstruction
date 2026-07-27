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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_company_id')
                ->nullable()
                ->constrained('companies')
                ->nullOnDelete();
            $table->string('name');
            $table->string('legal_name')->nullable();
            $table->string('slug')->unique();
            $table->string('registration_number')->nullable();
            $table->string('tax_number')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('website')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->char('country_code', 2)->default('PK');
            $table->char('currency_code', 3)->default('PKR');
            $table->string('timezone')->default('Asia/Karachi');
            $table->string('logo_path')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['parent_company_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
