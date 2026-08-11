<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cache for AI-generated "why is this the right answer" explanations on the quiz review page.
 *
 * An explanation depends only on the question, the reader's language, and which (wrong) options the
 * student picked - never on which student picked them. So two students who chose the same wrong
 * option share one cached row, and Claude is called once per distinct (question, locale, answer).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('answer_explanations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 2);
            // sha1 of the student's sorted selected option ids - the "which wrong answer" key.
            $table->string('answer_key', 40);
            $table->text('explanation');
            $table->timestamps();

            $table->unique(['question_id', 'locale', 'answer_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('answer_explanations');
    }
};
