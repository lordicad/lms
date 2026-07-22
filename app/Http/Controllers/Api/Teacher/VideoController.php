<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\User;
use App\Services\OwnershipService;
use App\Support\Uploads;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Teacher video create/edit for the mobile app. Mirrors the web Cikgu\LessonController and
 * reuses the same validation limits (config lms.video_*) and OwnershipService attribution, so
 * a video accepted on mobile is exactly one the web would accept and counts for talent the
 * same way.
 *
 * Both sources are supported: an uploaded MP4/WEBM (multipart) or a YouTube link.
 */
class VideoController extends Controller
{
    public function store(Request $request, OwnershipService $ownership): JsonResponse
    {
        $teacher = $request->user();
        if (! $teacher || ! $teacher->isTeacher()) {
            return response()->json(['message' => 'Hanya guru boleh mengakses ini.'], 403);
        }

        $data = $this->validated($request);
        $source = $data['source'];

        $payload = [
            'chapter_id' => $data['chapter_id'],
            'teacher_id' => $teacher->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'source' => $source,
            'is_published' => $data['is_published'] ?? true,
        ];

        if ($source === Lesson::SOURCE_UPLOAD) {
            // Lesson::saving() marks uploads as own work that counts for talent.
            $payload['video_path'] = Uploads::store($request->file('video'), 'videos');
        } else {
            $attribution = $this->attributeYoutube($request, $ownership, $teacher);
            if ($attribution instanceof JsonResponse) {
                return $attribution;
            }
            $payload += $attribution;
        }

        $lesson = Lesson::create($payload);

        return response()->json(['id' => $lesson->id], 201);
    }

    public function update(Request $request, Lesson $lesson, OwnershipService $ownership): JsonResponse
    {
        $teacher = $request->user();
        if (! $teacher || ! $teacher->isTeacher() || $lesson->teacher_id !== $teacher->id) {
            return response()->json(['message' => 'Anda tidak dibenarkan menyunting video ini.'], 403);
        }

        $data = $this->validated($request, $lesson);
        $source = $data['source'];

        // Read the current path first: Lesson::saving() nulls whichever column the new source
        // does not use, so a swapped-out upload would otherwise be orphaned on disk.
        $oldVideoPath = $lesson->video_path;
        $staleVideo = null;

        $lesson->fill([
            'chapter_id' => $data['chapter_id'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'source' => $source,
            'is_published' => $data['is_published'] ?? true,
        ]);

        if ($source === Lesson::SOURCE_UPLOAD) {
            if ($request->hasFile('video')) {
                $lesson->video_path = Uploads::store($request->file('video'), 'videos');
                $staleVideo = $oldVideoPath;          // replaced with a new file
            } else {
                $lesson->video_path = $oldVideoPath;  // keep what is already there
            }
        } else {
            $attribution = $this->attributeYoutube($request, $ownership, $teacher);
            if ($attribution instanceof JsonResponse) {
                return $attribution;
            }
            $lesson->fill($attribution);
            $staleVideo = $oldVideoPath;              // switched from upload to YouTube
        }

        $lesson->save();

        // Only after the row is safely saved do we drop the file it no longer points at.
        if ($staleVideo) {
            Storage::disk('uploads')->delete($staleVideo);
        }

        return response()->json(['id' => $lesson->id]);
    }

    /**
     * The same rules and messages as the web LessonRequest. `source` falls back to the lesson's
     * current source (or youtube) so older clients that only sent a link keep working.
     *
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Lesson $lesson = null): array
    {
        $request->merge([
            'source' => $request->input('source') ?: ($lesson?->source ?? Lesson::SOURCE_YOUTUBE),
        ]);

        $source = $request->input('source');
        $videoMax = (int) config('lms.video_max_mb') * 1024; // validator wants kilobytes

        // Editing an upload may keep its existing file, so a file is only required when
        // creating an upload or switching an existing lesson to one.
        $needsFile = $source === Lesson::SOURCE_UPLOAD
            && ! ($lesson && $lesson->source === Lesson::SOURCE_UPLOAD && $lesson->video_path);

        return $request->validate([
            'chapter_id' => ['required', 'integer', Rule::exists('chapters', 'id')],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'source' => ['required', Rule::in([Lesson::SOURCE_UPLOAD, Lesson::SOURCE_YOUTUBE])],
            'video' => [
                Rule::requiredIf($needsFile),
                'nullable',
                'file',
                'mimes:'.implode(',', config('lms.video_mimes')),
                'mimetypes:'.implode(',', config('lms.video_mimetypes')),
                "max:{$videoMax}",
            ],
            'youtube_url' => [
                Rule::requiredIf($source === Lesson::SOURCE_YOUTUBE),
                'nullable',
                'string',
                'max:500',
            ],
            'is_published' => ['boolean'],
        ], [
            'chapter_id.required' => __('Sila pilih Subjek, Tahun dan Bab.'),
            'title.required' => __('Sila isi tajuk video.'),
            'video.required' => __('Sila pilih fail video untuk dimuat naik.'),
            'video.mimes' => __('Format video mesti MP4 atau WEBM.'),
            'video.mimetypes' => __('Fail itu bukan video MP4/WEBM yang sah.'),
            'video.max' => __('Saiz video terlalu besar. Had ialah :max MB. Untuk rakaman panjang, sila guna pautan YouTube.', ['max' => config('lms.video_max_mb')]),
            'youtube_url.required' => __('Sila tampal pautan YouTube.'),
        ]);
    }

    /**
     * Attribution columns for a YouTube lesson, or an error response when the link is invalid
     * or the video is private/deleted.
     *
     * @return array<string, mixed>|JsonResponse
     */
    private function attributeYoutube(
        Request $request,
        OwnershipService $ownership,
        User $teacher,
    ): array|JsonResponse {
        $youtubeId = $this->extractYoutubeId((string) $request->input('youtube_url'));

        if ($youtubeId === null) {
            return response()->json(['message' => 'Pautan YouTube tidak sah.'], 422);
        }

        $result = $ownership->attributeYoutube($youtubeId, $teacher);

        if ($result['status'] === OwnershipService::STATUS_BLOCKED) {
            return response()->json([
                'message' => 'Video ini peribadi atau telah dipadam. Tetapkan ke Unlisted atau Public.',
            ], 422);
        }

        return [
            'youtube_id' => $youtubeId,
            'ownership' => $result['ownership'],
            'counts_for_talent' => $result['counts_for_talent'],
            'youtube_channel_id' => $result['youtube_channel_id'],
        ];
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
