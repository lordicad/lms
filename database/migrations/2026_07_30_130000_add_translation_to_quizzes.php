<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Second-language version of the quiz title and description, mirroring the per-question
     * translation columns. `source_locale` is the language the teacher typed; the *_translated
     * columns hold the other one. Null source_locale means the original shows in both languages.
     */
    public function up(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->string('source_locale', 2)->nullable()->after('description');
            $table->string('title_translated')->nullable()->after('source_locale');
            $table->text('description_translated')->nullable()->after('title_translated');
        });
    }

    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropColumn(['source_locale', 'title_translated', 'description_translated']);
        });
    }
};
