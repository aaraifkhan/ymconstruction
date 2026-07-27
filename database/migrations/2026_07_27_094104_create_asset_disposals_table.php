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
        Schema::create('asset_disposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fixed_asset_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('proceeds_account_id')->nullable()->constrained('accounts')->restrictOnDelete();
            $table->date('disposal_date');
            $table->decimal('proceeds_amount', 19, 4)->default(0);
            $table->decimal('cost_amount', 19, 4);
            $table->decimal('accumulated_depreciation_amount', 19, 4);
            $table->decimal('carrying_amount', 19, 4);
            $table->decimal('gain_amount', 19, 4)->default(0);
            $table->decimal('loss_amount', 19, 4)->default(0);
            $table->string('status')->default('draft');
            $table->text('reason');
            $table->foreignId('prepared_by_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('posted_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('journal_entry_id')->nullable()->unique()->constrained()->restrictOnDelete();
            $table->foreignId('reversal_journal_entry_id')->nullable()->unique()->constrained('journal_entries')->restrictOnDelete();
            $table->foreignId('reversed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reversed_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status', 'disposal_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_disposals');
    }
};
