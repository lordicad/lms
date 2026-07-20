<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Extended profile fields for the teacher and student profiles.
 *
 * All nullable so the migration is safe over existing rows and so accounts that predate the fields
 * (or roles that don't use them) stay valid. Teachers use phone/position; students use the guardian
 * trio. Both use school_id + school_class_id. Homeroom lives on school_classes, not here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('school_id')->nullable()->after('grade_id')
                ->constrained()->nullOnDelete();

            // A student's class. Nulled if the class is deleted, so the account is never orphaned.
            $table->foreignId('school_class_id')->nullable()->after('school_id')
                ->constrained('school_classes')->nullOnDelete();

            $table->string('phone')->nullable()->after('school_class_id');      // teacher contact
            $table->string('position')->nullable()->after('phone');            // teacher position/jawatan

            $table->string('guardian_name')->nullable()->after('position');    // student guardian
            $table->string('guardian_phone')->nullable()->after('guardian_name');
            $table->string('guardian_email')->nullable()->after('guardian_phone');

            $table->index('school_id');
            $table->index('school_class_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('school_id');
            $table->dropConstrainedForeignId('school_class_id');
            $table->dropColumn([
                'phone', 'position', 'guardian_name', 'guardian_phone', 'guardian_email',
            ]);
        });
    }
};
