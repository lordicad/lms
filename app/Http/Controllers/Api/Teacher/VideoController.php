<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Chapter;
use App\Models\Lesson;
use App\Services\OwnershipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Teacher video creation for the mobile app — YouTube links only for now (upload comes later).
 * Mirrors the web Cikgu\LessonController@store: it reuses OwnershipService to attribute the
 * video, so talent-score counting stays identical to the web. A private/deleted video is
 * rejected; a network hiccup during verification is deferred (video still saved), never a crash.
 */
class VideoController extends Controller
{
    public function store(Request $request, OwnershipService $ownership): JsonResponse
    {
        $teacher = $request->user();
        if (! $teacher || ! $teacher->isTeacher()) {
            return response()->json(['message' => 'Hanya guru boleh mengakses ini.'], 403);
        }

        $data = $request->validate([
            'chapter_id' => ['required', 'integer', Rule::exists('chapters', 'id')],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'youtube_url' => ['required', 'string', 'max:500'],
            'is_published' => ['boolean'],
        ]);

        $youtubeId = $this->extractYoutubeId($data['youtube_url']);
        if ($youtubeId === null) {
            return response()->json(['message' => 'Pautan YouTube tidak sah.'], 422);
        }

        $result = $ownership->attributeYoutube($youtubeId, $teacher);
        if ($result['status'] === OwnershipService::STATUS_BLOCKED) {
            return response()->json([
                'message' => 'Video ini peribadi atau telah dipadam. Tetapkan ke Unlisted atau Public.',
            ], 422);
        }

        $lesson = Lesson::create([
            'chapter_id' => $data['chapter_id'],
            'teacher_id' => $teacher->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'source' => Lesson::SOURCE_YOUTUBE,
            'youtube_id' => $youtubeId,
            'ownership' => $result['ownership'],
            'counts_for_talent' => $result['counts_for_talent'],
            'youtube_channel_id' => $result['youtube_channel_id'],
            'is_published' => $data['is_published'] ?? true,
        ]);

        return response()->json(['id' => $lesson->id], 201);
    }

    public function update(Request $request, Lesson $lesson, OwnershipService $ownership): JsonResponse
    {
        $teacher = $request->user();
        if (! $teacher || ! $teacher->isTeacher() || $lesson->teacher_id !== $teacher->id) {
            return response()->json(['message' => 'Anda tidak dibenarkan menyunting video ini.'], 403);
        }

        $data = $request->validate([
            'chapter_id' => ['required', 'integer', Rule::exists('chapters', 'id')],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'youtube_url' => ['required', 'string', 'max:500'],
            'is_published' => ['boolean'],
        ]);

        $youtubeId = $this->extractYoutubeId($data['youtube_url']);
        if ($youtubeId === null) {
            return response()->json(['message' => 'Pautan YouTube tidak sah.'], 422);
        }

        $result = $ownership->attributeYoutube($youtubeId, $teacher);
        if ($result['status'] === OwnershipService::STATUS_BLOCKED) {
            return response()->json([
                'message' => 'Video ini peribadi atau telah dipadam. Tetapkan ke Unlisted atau Public.',
            ], 422);
        }

        $lesson->update([
            'chapter_id' => $data['chapter_id'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'source' => Lesson::SOURCE_YOUTUBE,
            'youtube_id' => $youtubeId,
            'ownership' => $result['ownership'],
            'counts_for_talent' => $result['counts_for_talent'],
            'youtube_channel_id' => $result['youtube_channel_id'],
            'is_published' => $data['is_published'] ?? true,
        ]);

        return response()->json(['id' => $lesson->id]);
    }

    /**
     * Pull the 11-char video id out of a YouTube URL (watch, youtu.be, embed, shorts) or a
     * bare id.
     */
    private function extractYoutubeId(string $url): ?string
    {
        $url = trim($url);

        if (preg_match('/^[A-Za-z0-9_-]{11}$/', $url)) {
            return $url;
        }

        if (preg_match('~(?:youtube\.com/(?:watch\?v=|embed/|shorts/)|youtu\.be/)([A-Za-z0-9_-]{11})~', $url, $m)) {
            return $m[1];
        }

        if (preg_match('~[?&]v=([A-Za-z0-9_-]{11})~', $url, $m)) {
            return $m[1];
        }

        return null;
    }
}
