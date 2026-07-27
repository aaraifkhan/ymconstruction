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
        Schema::create('joining_letters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employment_id')->constrained()->restrictOnDelete();
            $table->foreignId('joining_letter_template_id')->nullable()->constrained()->nullOnDelete();
            $table->string('letter_number', 100);
            $table->string('status');
            $table->string('subject');
            $table->text('body');
            $table->text('compensation_amount')->nullable();
            $table->string('currency_code', 3)->default('PKR');
            $table->date('letter_date');
            $table->date('employment_effective_date');
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('submitted_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('approved_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('rejected_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('issued_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('issued_at')->nullable();
            $table->string('accepted_by_name')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->text('acceptance_notes')->nullable();
            $table->string('content_checksum', 64)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'letter_number']);
            $table->index(['company_id', 'status']);
            $table->index(['employment_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('joining_letters');
    }
};
