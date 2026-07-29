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
        Schema::create('employment_separations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employment_id')->constrained()->restrictOnDelete();
            $table->string('reference_number')->nullable();
            $table->string('type');
            $table->string('status')->default('draft');
            $table->date('request_date');
            $table->date('proposed_last_working_date');
            $table->date('approved_last_working_date')->nullable();
            $table->unsignedInteger('notice_days_required')->nullable();
            $table->unsignedInteger('notice_days_served')->nullable();
            $table->text('reason');
            $table->text('authority')->nullable();
            $table->text('protected_notes')->nullable();
            $table->text('handover_notes')->nullable();
            $table->string('access_review_status')->default('pending');
            $table->foreignId('access_reviewed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('access_reviewed_at')->nullable();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('submitted_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('accepted_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('accepted_at')->nullable();
            $table->foreignId('approved_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('withdrawn_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('withdrawn_at')->nullable();
            $table->text('withdrawal_reason')->nullable();
            $table->foreignId('rejected_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['company_id', 'reference_number']);
            $table->index(['company_id', 'type', 'status', 'proposed_last_working_date']);
            $table->index(['employment_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employment_separations');
    }
};
