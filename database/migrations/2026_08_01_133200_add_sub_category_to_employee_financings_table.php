<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_financings', function (Blueprint $table) {
            $table->string('sub_category')->nullable()->after('type');

            $table->index(['company_id', 'type', 'sub_category']);
        });
    }

    public function down(): void
    {
        Schema::table('employee_financings', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'type', 'sub_category']);
            $table->dropColumn('sub_category');
        });
    }
};
