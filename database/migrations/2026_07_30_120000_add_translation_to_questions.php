<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Store each question's second-language version alongside the original so the language toggle
     * can pick the right one without translating on every page view. `source_locale` records the
     * language the teacher typed in ('ms' or 'en'); the *_translated columns hold the other one.
     * A null source_locale means "no translation" — the original text is shown in both languages.
     */
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->string('source_locale', 2)->nullable()->after('question_text');
            $table->text('question_text_translated')->nullable()->after('source_locale');
        });

        Schema::table('question_options', function (Blueprint $table) {
            $table->string('option_text_translated', 500)->nullable()->after('option_text');
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn(['source_locale', 'question_text_translated']);
        });

        Schema::table('question_options', function (Blueprint $table) {
            $table->dropColumn('option_text_translated');
        });
    }
};
