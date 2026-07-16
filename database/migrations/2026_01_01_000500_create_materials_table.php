<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Bahan bantu mengajar: slides, PDF, worksheets. Optional support for a lesson.
        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chapter_id')->constrained()->cascadeOnDelete();

            // A material can hang off a chapter alone, or be attached to one lesson.
            // Deleting the lesson keeps the material on the chapter.
            $table->foreignId('lesson_id')->nullable()->constrained()->nullOnDelete();

            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('file_path');
            $table->string('original_name');
            $table->string('mime_type', 128);
            $table->unsignedInteger('size_kb');
            $table->unsignedInteger('download_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materials');
    }
};
