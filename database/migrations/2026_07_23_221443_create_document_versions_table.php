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
        Schema::create('document_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('disk')->default('local');
            $table->text('path');
            $table->string('original_file_name');
            $table->string('mime_type', 150);
            $table->string('extension', 20)->nullable();
            $table->unsignedBigInteger('size');
            $table->string('checksum', 64);
            $table->foreignId('uploaded_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['document_id', 'version']);
            $table->unique(['disk', 'path']);
            $table->index('checksum');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_versions');
    }
};
