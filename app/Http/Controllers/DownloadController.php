<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadController extends Controller
{
    /**
     * Downloads are served through PHP rather than linked straight at the file so the counter
     * is honest and the student gets the original filename back, not the stored uuid.
     */
    public function material(Request $request, Material $material): StreamedResponse
    {
        $this->authorize('download', $material);

        $disk = Storage::disk('uploads');

        abort_unless($disk->exists($material->file_path), Response::HTTP_NOT_FOUND);

        $material->increment('download_count');

        return $disk->download($material->file_path, $material->original_name);
    }

    public function quiz(Request $request, Quiz $quiz): StreamedResponse
    {
        abort_unless($quiz->isFile() && $quiz->file_path, Response::HTTP_NOT_FOUND);
        abort_unless($quiz->is_published || $request->user()->id === $quiz->teacher_id, Response::HTTP_NOT_FOUND);

        $disk = Storage::disk('uploads');

        abort_unless($disk->exists($quiz->file_path), Response::HTTP_NOT_FOUND);

        return $disk->download($quiz->file_path, $quiz->original_name ?? 'kuiz.pdf');
    }
}
