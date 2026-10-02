<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Closes the private upload folders to direct web access.
 *
 * Videos, materials and printable quizzes used to be served straight from <docroot>/uploads, so
 * anyone holding a file's URL could open it - drafts and other schools' content included - and
 * the publish/grade checks in the controllers were advisory only. MediaController now serves them
 * through short-lived signed URLs; this drops a deny-all .htaccess into each folder so the old
 * direct paths answer 403.
 *
 * This is a migration (not a manual server step) because migrate is the one part of the deploy
 * that runs with the app's real `uploads` disk, which on this split deployment lives in the
 * separate web docroot outside git. It changes no data and never throws: a failure is logged and
 * the deploy carries on (files stay reachable as before, nothing breaks).
 *
 * Thumbnails and avatars stay public: they are shown as plain <img> tags.
 */
return new class extends Migration
{
    private const FOLDERS = ['videos', 'materials', 'quizzes'];

    private const RULES = <<<'HTACCESS'
# Written by the protect_private_uploads migration. Files here are served only through
# signed URLs (MediaController); direct requests are refused.
<IfModule mod_authz_core.c>
    Require all denied
</IfModule>
<IfModule !mod_authz_core.c>
    Order allow,deny
    Deny from all
</IfModule>
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^ - [F]
</IfModule>

HTACCESS;

    public function up(): void
    {
        foreach (self::FOLDERS as $folder) {
            try {
                // The uploads disk is configured not to throw, so a refused write returns false.
                if (! Storage::disk('uploads')->put("{$folder}/.htaccess", self::RULES)) {
                    Log::warning("protect_private_uploads: could not write {$folder}/.htaccess");
                }
            } catch (\Throwable $e) {
                Log::warning("protect_private_uploads: could not write {$folder}/.htaccess", ['error' => $e->getMessage()]);
            }
        }
    }

    public function down(): void
    {
        foreach (self::FOLDERS as $folder) {
            try {
                Storage::disk('uploads')->delete("{$folder}/.htaccess");
            } catch (\Throwable $e) {
                Log::warning("protect_private_uploads: could not remove {$folder}/.htaccess", ['error' => $e->getMessage()]);
            }
        }
    }
};
