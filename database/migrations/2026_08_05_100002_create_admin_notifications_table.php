<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Notifications shown in the admin bell. Mirrors teacher_notifications but scoped to a school
 * rather than a single user: every admin of that school sees them, since any of them can act on it.
 * The first use is a forgot-password request — a user proved control of their email and now needs
 * an admin to reset the password.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_notifications', function (Blueprint $table) {
            $table->id();
            // Nullable: a user with no school produces an unscoped notification every admin can see.
            $table->foreignId('school_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('type', 40);
            $table->string('actor_name');         // the user the notification is about
            $table->string('title');              // denormalised detail (e.g. the username)
            $table->string('url')->nullable();    // where acting on it takes the admin
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_notifications');
    }
};
