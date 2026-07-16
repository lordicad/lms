<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kurikulum Persekolahan 2027 restructure.
 *
 * Subjects gain a category and an optional short name; availability becomes a proper
 * many-to-many between grades and subjects (a subject is only offered in certain Tahun);
 * and chapters gain an is_active flag so content stranded in a no-longer-offered
 * (subject, Tahun) can be hidden without ever being deleted.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->string('category')->default('')->index()->after('slug');
            $table->string('short_name')->nullable()->after('name');
        });

        Schema::create('grade_subject', function (Blueprint $table) {
            $table->foreignId('grade_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();

            $table->unique(['grade_id', 'subject_id']);
        });

        Schema::table('chapters', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('chapters', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });

        Schema::dropIfExists('grade_subject');

        Schema::table('subjects', function (Blueprint $table) {
            $table->dropColumn(['category', 'short_name']);
        });
    }
};
