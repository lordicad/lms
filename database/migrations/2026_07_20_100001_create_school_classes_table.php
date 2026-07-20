<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A class ("darjah") within a school — e.g. "6 Bestari". It belongs to a school and to a Tahun
 * (grade), so class lists can be filtered by School + Year.
 *
 * homeroom_teacher_id is the single source of truth for the homeroom relationship (see the brief's
 * data-model note): a class has at most one homeroom teacher, a teacher's "homeroom class" is the
 * class that points back at them, and a student's homeroom teacher is derived read-only from their
 * class. The unique index enforces one-class-per-teacher. Deleting the teacher only nulls the link;
 * the class and its students are untouched.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('grade_id')->constrained()->cascadeOnDelete();
            $table->string('name');   // "Bestari", "Cerdik" — combined with the Tahun for display
            $table->foreignId('homeroom_teacher_id')->nullable()->unique()
                ->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['school_id', 'grade_id', 'name']);
            $table->index(['school_id', 'grade_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_classes');
    }
};
