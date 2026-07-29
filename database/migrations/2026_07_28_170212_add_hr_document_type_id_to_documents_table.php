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
        Schema::table('documents', function (Blueprint $table) {
            $table->foreignId('hr_document_type_id')
                ->nullable()
                ->after('document_category_id')
                ->constrained()
                ->restrictOnDelete();
            $table->index(['company_id', 'hr_document_type_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['hr_document_type_id']);
            $table->dropIndex(['company_id', 'hr_document_type_id']);
            $table->dropColumn('hr_document_type_id');
        });
    }
};
