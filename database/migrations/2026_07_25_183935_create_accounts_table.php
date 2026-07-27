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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('accounts')->restrictOnDelete();
            $table->foreignId('account_template_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('code', 50);
            $table->string('name');
            $table->string('account_type');
            $table->string('reporting_group', 100);
            $table->string('normal_balance');
            $table->string('system_key', 100)->nullable();
            $table->boolean('is_control_account')->default(false);
            $table->boolean('allows_manual_posting')->default(false);
            $table->boolean('is_system_generated')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'code']);
            $table->unique(['company_id', 'system_key']);
            $table->index(['company_id', 'parent_id', 'sort_order']);
            $table->index(['company_id', 'account_type', 'is_active']);
            $table->index(['company_id', 'reporting_group']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
