<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employments', function (Blueprint $table) {
            $table->foreignId('cost_center_id')->nullable()->after('work_location_id')
                ->constrained('cost_centers')->nullOnDelete();
            $table->foreignId('default_project_id')->nullable()->after('cost_center_id')
                ->constrained('projects')->nullOnDelete();

            $table->index(['company_id', 'cost_center_id']);
            $table->index(['company_id', 'default_project_id']);
        });
    }

    public function down(): void
    {
        Schema::table('employments', function (Blueprint $table) {
            $table->dropForeign(['cost_center_id']);
            $table->dropForeign(['default_project_id']);
            $table->dropIndex(['company_id', 'cost_center_id']);
            $table->dropIndex(['company_id', 'default_project_id']);
            $table->dropColumn(['cost_center_id', 'default_project_id']);
        });
    }
};
