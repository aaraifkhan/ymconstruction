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
        Schema::create('attendance_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->unsignedSmallInteger('grace_minutes');
            $table->unsignedSmallInteger('late_rounding_minutes');
            $table->unsignedSmallInteger('half_day_after_minutes');
            $table->unsignedSmallInteger('absence_after_minutes');
            $table->unsignedSmallInteger('minimum_overtime_minutes');
            $table->string('missing_punch_treatment');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['company_id', 'effective_from', 'effective_to']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_rules');
    }
};
