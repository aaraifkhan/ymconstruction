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
        Schema::create('department_team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_team_id')->constrained('department_teams')->cascadeOnDelete();
            $table->foreignId('employment_id')->constrained('employments')->cascadeOnDelete();
            $table->string('role_in_team')->default('member');
            $table->date('joined_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['department_team_id', 'employment_id']);
            $table->index(['employment_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('department_team_members');
    }
};
