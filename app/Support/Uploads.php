<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Uploads
{
    /**
     * Stores a file on the `uploads` disk under a freshly generated uuid name.
     *
     * Regenerating the name is what defeats double-extension tricks: `nota.php.pdf` lands on
     * disk as `9f1c...-b3.pdf`, so there is no path by which the web server would ever treat
     * it as a script (public/uploads/.htaccess denies execution as a second layer).
     * The original filename is kept in the database purely for display and download.
     */
    public static function store(UploadedFile $file, string $folder): string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension());
        $name = Str::uuid()->toString().'.'.$extension;

        Storage::disk('uploads')->putFileAs($folder, $file, $name);

        return "{$folder}/{$name}";
    }

    public static function sizeKb(UploadedFile $file): int
    {
        return (int) max(1, ceil($file->getSize() / 1024));
    }
}
