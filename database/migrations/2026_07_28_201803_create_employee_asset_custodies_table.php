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
        Schema::create('employee_asset_custodies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fixed_asset_id')->constrained()->restrictOnDelete();
            $table->foreignId('employment_id')->constrained()->restrictOnDelete();
            $table->string('reference_number')->nullable();
            $table->string('status')->default('draft');
            $table->date('issued_on');
            $table->date('due_on')->nullable();
            $table->date('returned_on')->nullable();
            $table->string('issued_condition');
            $table->text('accessories')->nullable();
            $table->string('issued_location')->nullable();
            $table->text('issue_notes')->nullable();
            $table->string('return_condition')->nullable();
            $table->text('return_notes')->nullable();
            $table->string('exception_type')->nullable();
            $table->text('exception_notes')->nullable();
            $table->text('recovery_recommendation_amount')->nullable();
            $table->text('recovery_recommendation_notes')->nullable();
            $table->foreignId('prepared_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('issued_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('issued_at')->nullable();
            $table->foreignId('acknowledged_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('acknowledged_at')->nullable();
            $table->foreignId('return_requested_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('return_requested_at')->nullable();
            $table->foreignId('returned_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('returned_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'reference_number']);
            $table->index(['company_id', 'employment_id', 'status']);
            $table->index(['fixed_asset_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_asset_custodies');
    }
};
