<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add the MOE oversight role. MySQL/MariaDB enum syntax — tests and production are both
        //    MySQL-family (see phpunit.xml), so a raw MODIFY is safe and portable enough here.
        DB::statement("ALTER TABLE users MODIFY role ENUM('admin','teacher','student') NOT NULL DEFAULT 'student'");

        // 2. Verified YouTube channels a teacher owns. A teacher may own several (brand accounts),
        //    hence a table rather than a column. OAuth tokens are deliberately NOT stored.
        Schema::create('youtube_channels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->string('channel_id')->unique();   // one channel = at most one teacher
            $table->string('title');
            $table->string('thumbnail_url')->nullable();
            $table->timestamp('verified_at');
            $table->timestamps();

            $table->index('teacher_id');
        });

        // 3. Ownership attribution on each lesson.
        Schema::table('lessons', function (Blueprint $table) {
            $table->string('youtube_channel_id')->nullable()->after('youtube_id');   // channelId snapshot at save
            $table->enum('ownership', ['owned', 'reference', 'upload'])->default('upload')->after('thumbnail_path');
            $table->boolean('counts_for_talent')->default(true)->after('ownership');
        });

        // Backfill: direct uploads are always the teacher's own work and count. Pre-existing YouTube
        // links are references (unverified) and excluded until the teacher connects the owning
        // channel — re-attribution then flips the matching ones to owned.
        DB::table('lessons')->where('source', 'youtube')->update([
            'ownership' => 'reference',
            'counts_for_talent' => false,
        ]);
        DB::table('lessons')->where('source', 'upload')->update([
            'ownership' => 'upload',
            'counts_for_talent' => true,
        ]);
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn(['youtube_channel_id', 'ownership', 'counts_for_talent']);
        });

        Schema::dropIfExists('youtube_channels');

        // Revert any admins to teacher first, so the narrower enum can accept every existing row.
        DB::table('users')->where('role', 'admin')->update(['role' => 'teacher']);
        DB::statement("ALTER TABLE users MODIFY role ENUM('teacher','student') NOT NULL DEFAULT 'student'");
    }
};
