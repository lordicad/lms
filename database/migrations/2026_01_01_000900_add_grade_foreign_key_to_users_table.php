<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * users.grade_id is declared in the users migration but the constraint has to wait
     * until `grades` exists. Deleting a grade nulls the column rather than deleting
     * the child accounts.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('grade_id')->references('id')->on('grades')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['grade_id']);
        });
    }
};
