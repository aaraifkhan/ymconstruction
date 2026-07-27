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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_category_id')->constrained()->restrictOnDelete();
            $table->nullableMorphs('documentable');
            $table->string('title');
            $table->string('reference_number')->nullable();
            $table->string('classification')->default('internal')->index();
            $table->string('status')->default('draft')->index();
            $table->date('issue_date')->nullable()->index();
            $table->date('expiry_date')->nullable()->index();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('verified_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('approved_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('rejected_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'document_category_id']);
            $table->index(['company_id', 'status', 'expiry_date']);
            $table->unique(['company_id', 'reference_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
