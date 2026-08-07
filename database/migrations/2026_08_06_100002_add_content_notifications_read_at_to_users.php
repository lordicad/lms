<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-student "last opened my notifications" marker. Content notifications are a shared feed, so a
 * student's unread count is everything in their feed newer than this timestamp; opening the bell or
 * the notifications page stamps it to now.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('content_notifications_read_at')->nullable()->after('theme');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('content_notifications_read_at');
        });
    }
};
