<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Teacher activity notifications: a student took one of their quizzes, favourited one of
 * their videos, or downloaded one of their materials. Actor name and content title are
 * denormalised so a notification still reads correctly if the student or content is later
 * removed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 30);            // quiz_attempt | favourite | download
            $table->string('actor_name');          // the student who did it
            $table->string('title');               // the quiz / video / material title
            $table->string('url')->nullable();     // where the bell click should take the teacher
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['teacher_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_notifications');
    }
};
