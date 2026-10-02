<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\Material;
use App\Models\Quiz;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Serves the private uploads (lesson videos, materials, printable quizzes).
 *
 * Those folders are closed to direct web access (see the protect_private_uploads migration), so
 * the only way to a file is a short-lived signed URL that the app hands out on a page the viewer
 * was already allowed to see - Lesson::videoUrl(), Material::fileUrl(), Quiz::fileUrl(). The
 * signature stands in for a login, which is what lets the Flutter app (bearer tokens, no session
 * cookie) play the same URLs. A leaked link stops working when it expires.
 *
 * BinaryFileResponse honours HTTP Range requests, so scrubbing through a video still works.
 */
class MediaController extends Controller
{
    public function lesson(Lesson $lesson): BinaryFileResponse
    {
        return $this->serve($lesson->video_path);
    }

    public function material(Material $material): BinaryFileResponse
    {
        return $this->serve($material->file_path);
    }

    public function quiz(Quiz $quiz): BinaryFileResponse
    {
        return $this->serve($quiz->file_path);
    }

    private function serve(?string $path): BinaryFileResponse
    {
        $disk = Storage::disk('uploads');

        abort_unless($path && $disk->exists($path), 404);

        return response()->file($disk->path($path), [
            // The URL is personal and short-lived; keep it out of shared caches.
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
