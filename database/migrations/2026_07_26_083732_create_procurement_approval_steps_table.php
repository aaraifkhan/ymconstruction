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
        Schema::create('procurement_approval_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->morphs('approvable');
            $table->foreignId('procurement_approval_rule_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('approval_round');
            $table->unsignedSmallInteger('step_number');
            $table->string('name');
            $table->string('permission_name');
            $table->string('status', 30)->default('pending');
            $table->foreignId('decided_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->text('decision_reason')->nullable();
            $table->timestamps();

            $table->unique(
                ['approvable_type', 'approvable_id', 'approval_round', 'step_number'],
                'procurement_approval_steps_document_step_unique',
            );
            $table->index(['company_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procurement_approval_steps');
    }
};
