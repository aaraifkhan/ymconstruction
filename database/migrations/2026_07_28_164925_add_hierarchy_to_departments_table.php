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
        Schema::table('departments', function (Blueprint $table) {
            $table->foreignId('parent_department_id')
                ->nullable()
                ->after('company_id')
                ->constrained('departments')
                ->nullOnDelete();
            $table->index(['company_id', 'parent_department_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropForeign(['parent_department_id']);
            $table->dropIndex(['company_id', 'parent_department_id']);
            $table->dropColumn('parent_department_id');
        });
    }
};
