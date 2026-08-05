<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One-time codes for the forgot-password flow. The code proves the person requesting a reset can
 * read the account's email (a teacher's own, a student's guardian's) before the request is raised
 * with an admin. Only the hash is stored — the plain code lives only in the email — and each row is
 * short-lived and single-use.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('password_otps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('otp_hash');
            $table->string('sent_to');            // the address the code was emailed to, for audit/display
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_otps');
    }
};
