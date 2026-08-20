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
        Schema::table('fixed_assets', function (Blueprint $table) {
            $table->foreignId('assigned_company_id')->nullable()->after('company_id')->constrained('companies')->nullOnDelete();
            $table->string('custody_status', 40)->default('in_pool')->after('status');
            $table->timestamp('assigned_at')->nullable()->after('custody_status');
            $table->string('condition_on_assignment', 100)->nullable()->after('assigned_at');
            $table->text('handover_notes')->nullable()->after('condition_on_assignment');

            $table->index(['assigned_company_id', 'custody_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fixed_assets', function (Blueprint $table) {
            $table->dropIndex(['assigned_company_id', 'custody_status']);
            $table->dropForeign(['assigned_company_id']);
            $table->dropColumn([
                'assigned_company_id',
                'custody_status',
                'assigned_at',
                'condition_on_assignment',
                'handover_notes',
            ]);
        });
    }
};
