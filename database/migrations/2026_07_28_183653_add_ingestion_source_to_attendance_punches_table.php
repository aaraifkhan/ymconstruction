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
        Schema::table('attendance_punches', function (Blueprint $table) {
            $table->string('source')->default('manual')->after('direction');
            $table->foreignId('attendance_raw_event_id')
                ->nullable()
                ->unique()
                ->after('source')
                ->constrained()
                ->restrictOnDelete();
            $table->foreignId('created_by_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_punches', function (Blueprint $table) {
            $table->foreignId('created_by_id')->nullable(false)->change();
            $table->dropConstrainedForeignId('attendance_raw_event_id');
            $table->dropColumn('source');
        });
    }
};
