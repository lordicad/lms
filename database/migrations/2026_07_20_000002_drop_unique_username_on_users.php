<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Usernames (nama pengguna) may now repeat — many users can share the same nickname.
 * Login still resolves fine because teachers sign in with their unique email; email
 * stays unique. We keep username indexed (non-unique) for fast lookups.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->index('username');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['username']);
            $table->unique('username');
        });
    }
};
