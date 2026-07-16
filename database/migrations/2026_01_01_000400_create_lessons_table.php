<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chapter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();

            $table->enum('source', ['upload', 'youtube']);

            // Exactly one of these is set. Enforced in Lesson::booted() and in the form requests,
            // because MySQL 5.7 and older MariaDB builds ignore CHECK constraints.
            $table->string('video_path')->nullable();
            $table->string('youtube_id', 20)->nullable();

            $table->string('thumbnail_path')->nullable();
            $table->unsignedInteger('views_count')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();

            $table->index(['chapter_id', 'is_published']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
