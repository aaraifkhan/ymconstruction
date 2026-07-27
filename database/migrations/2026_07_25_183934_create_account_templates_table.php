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
        Schema::create('account_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('account_templates')->restrictOnDelete();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->string('account_type');
            $table->string('reporting_group', 100);
            $table->string('normal_balance');
            $table->string('system_key', 100)->nullable()->unique();
            $table->json('activation_profiles')->nullable();
            $table->boolean('is_control_account')->default(false);
            $table->boolean('allows_manual_posting')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['parent_id', 'sort_order']);
            $table->index(['account_type', 'reporting_group']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_templates');
    }
};
