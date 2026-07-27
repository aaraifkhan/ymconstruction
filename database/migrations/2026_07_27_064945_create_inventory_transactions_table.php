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
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('transaction_number')->nullable();
            $table->string('type', 40);
            $table->string('status', 20)->default('draft');
            $table->date('transaction_date');
            $table->foreignId('source_site_id')->nullable()->constrained('project_sites')->restrictOnDelete();
            $table->foreignId('destination_site_id')->nullable()->constrained('project_sites')->restrictOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('goods_receipt_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('reference')->nullable();
            $table->text('reason');
            $table->foreignId('prepared_by_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('posted_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('journal_entry_id')->nullable()->unique()->constrained('journal_entries')->restrictOnDelete();
            $table->decimal('total_value', 19, 4)->default(0);
            $table->timestamps();

            $table->unique(['company_id', 'transaction_number']);
            $table->index(['company_id', 'status', 'transaction_date']);
            $table->index(['company_id', 'type', 'transaction_date']);
            $table->index(['source_site_id', 'status']);
            $table->index(['destination_site_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
    }
};
