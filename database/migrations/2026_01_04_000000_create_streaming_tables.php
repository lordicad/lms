<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Streaming-style student experience: per-student favourites and resumable watch progress.
 *
 * lesson_progress is separate from and additive to lesson_views — views still power the
 * teacher stats and the "first play counts once" rule; progress powers Continue Watching,
 * resume and completion.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('favourites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('lesson_id')->constrained('lessons')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['student_id', 'lesson_id']);
        });

        Schema::create('lesson_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('lesson_id')->constrained('lessons')->cascadeOnDelete();
            $table->unsignedInteger('position_seconds')->default(0);
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->unsignedTinyInteger('percent')->default(0);   // 0..100, derived
            $table->boolean('completed')->default(false);         // true at >= 90%
            $table->dateTime('last_watched_at')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'lesson_id']);
            $table->index(['student_id', 'last_watched_at']);
        });

        Schema::table('lessons', function (Blueprint $table) {
            $table->unsignedInteger('duration_seconds')->nullable()->after('thumbnail_path');
            $table->unsignedInteger('favourites_count')->default(0)->after('views_count');
        });
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn(['duration_seconds', 'favourites_count']);
        });

        Schema::dropIfExists('lesson_progress');
        Schema::dropIfExists('favourites');
    }
};
