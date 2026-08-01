<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_entries', function (Blueprint $table) {
            $table->text('fuel_allowance')->nullable()->after('house_travel_allowance');
            $table->text('mobile_allowance')->nullable()->after('fuel_allowance');
            $table->text('internet_allowance')->nullable()->after('mobile_allowance');
            $table->text('site_allowance')->nullable()->after('food_allowance');
            $table->text('project_allowance')->nullable()->after('site_allowance');
        });
    }

    public function down(): void
    {
        Schema::table('payroll_entries', function (Blueprint $table) {
            $table->dropColumn([
                'fuel_allowance',
                'mobile_allowance',
                'internet_allowance',
                'site_allowance',
                'project_allowance',
            ]);
        });
    }
};
