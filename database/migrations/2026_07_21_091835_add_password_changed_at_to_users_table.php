<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * When an admin creates an account they choose the first password and hand it over, so that
 * password is known to someone other than its owner. `password_changed_at` records the moment the
 * owner replaced it with one of their own: NULL means "still on the admin-issued password", which
 * is what forces the change screen at sign-in.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('password_changed_at')->nullable()->after('password');
        });

        // Existing accounts are already in use — stamping them keeps anyone from being dropped into
        // the change screen on their next sign-in. Only accounts created from now on start NULL.
        DB::table('users')->update(['password_changed_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('password_changed_at');
        });
    }
};
