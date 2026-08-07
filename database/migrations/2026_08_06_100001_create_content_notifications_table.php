<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A student-facing feed of newly published content. One row per uploaded video / material / quiz,
 * keyed by the teacher's school and the content's Tahun (grade) — so it is a shared broadcast that
 * every student in that school + year reads, rather than a copy per student. Read state is not on
 * the row (it is shared): each student tracks their own last-seen moment in
 * users.content_notifications_read_at.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();   // the uploading teacher's school
            $table->foreignId('grade_id')->constrained()->cascadeOnDelete();    // the content's Tahun
            $table->string('type', 12);              // video | material | quiz
            $table->unsignedBigInteger('content_id'); // the source row, for dedupe
            $table->string('actor_name');            // the teacher's name
            $table->string('title');                 // the content's title
            $table->string('url')->nullable();       // where opening it takes the student
            $table->timestamps();

            // A given item is announced once, even if its publish state is toggled repeatedly.
            $table->unique(['type', 'content_id']);
            // The student feed reads by school + year, newest first.
            $table->index(['school_id', 'grade_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_notifications');
    }
};
