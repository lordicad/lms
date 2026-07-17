<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lets MOE switch a teacher's access off without touching their work.
 *
 * Deactivating blocks sign-in only: the teacher's videos, materials and quizzes stay published
 * and students carry on using them. That is the point — a teacher leaving should not silently
 * pull lessons out from under a class mid-term.
 *
 * Defaults to true so every existing account keeps working through the deploy.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
