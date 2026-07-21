<?php

namespace App\Http\Controllers\Cikgu;

use App\Http\Controllers\Controller;
use App\Http\Requests\LessonRequest;
use App\Models\Grade;
use App\Models\Lesson;
use App\Models\Subject;
use App\Models\User;
use App\Services\OwnershipService;
use App\Support\Uploads;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LessonController extends Controller
{
    /**
     * JSON list of the teacher's own videos in a chapter, for the "Attach to a video"
     * dropdown on the material form. Mirrors ChapterController@lookup (/api/bab).
     */
    public function lookup(Request $request): \Illuminate\Http\JsonResponse
    {
        $chapterId = $request->integer('chapter');

        $lessons = $chapterId
            ? $request->user()->lessons()
                ->where('chapter_id', $chapterId)
                ->orderByDesc('id')
                ->get(['id', 'title'])
            : collect();

        return response()->json(
            $lessons->map(fn (Lesson $lesson) => [
                'id' => $lesson->id,
                'title' => $lesson->title,
            ])->values()
        );
    }

    public function index(Request $request): View
    {
        $teacher = $request->user();
        $filter = \App\Support\ContentFilter::fromRequest($request);

        $lessons = $filter->apply(
            $teacher->lessons()->with('chapter.subject', 'chapter.grade')
        )
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        return view('cikgu.video.index', [
            'lessons' => $lessons,
            'subjects' => Subject::orderBy('sort_order')->get(),
            'grades' => Grade::orderBy('level')->get(),
            'filter' => $filter,
            // All-time totals for this teacher (not the filtered page count).
            'totalVideos' => $teacher->lessons()->count(),
            'viewCount' => (int) $teacher->lessons()->sum('views_count'),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Lesson::class);

        return view('cikgu.video.form', [
            'lesson' => new Lesson(['source' => Lesson::SOURCE_YOUTUBE, 'is_published' => true]),
            'subjects' => Subject::orderBy('sort_order')->get(),
            'grades' => Grade::orderBy('level')->get(),
            'chapter' => null,
        ]);
    }

    public function store(LessonRequest $request, OwnershipService $ownership): RedirectResponse
    {
        $this->authorize('create', Lesson::class);

        $source = $request->input('source');

        $data = [
            'chapter_id' => $request->integer('chapter_id'),
            'teacher_id' => $request->user()->id,
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'source' => $source,
            'is_published' => $request->boolean('is_published'),
        ];

        $banner = null;

        if ($source === Lesson::SOURCE_UPLOAD) {
            $data['video_path'] = Uploads::store($request->file('video'), 'videos');
        } else {
            $data['youtube_id'] = $request->youtubeId();
            [$attribution, $banner] = $this->attribute($ownership, $request->youtubeId(), $request->user());
            $data += $attribution;
        }

        if ($request->hasFile('thumbnail')) {
            $data['thumbnail_path'] = Uploads::store($request->file('thumbnail'), 'thumbnails');
        }

        $lesson = Lesson::create($data);

        return $this->savedRedirect(
            __('Video ":title" berjaya disimpan.', ['title' => $lesson->title]),
            $banner,
        );
    }

    public function edit(Lesson $lesson): View
    {
        $this->authorize('update', $lesson);

        $lesson->load('chapter.subject', 'chapter.grade');

        return view('cikgu.video.form', [
            'lesson' => $lesson,
            'subjects' => Subject::orderBy('sort_order')->get(),
            'grades' => Grade::orderBy('level')->get(),
            'chapter' => $lesson->chapter,
        ]);
    }

    public function update(LessonRequest $request, Lesson $lesson, OwnershipService $ownership): RedirectResponse
    {
        $this->authorize('update', $lesson);

        // Read the current paths first. Lesson::saving() nulls whichever column the new source
        // does not use, so without this a swapped-out video would be orphaned on disk.
        $oldVideoPath = $lesson->video_path;
        $oldThumbnailPath = $lesson->thumbnail_path;

        $source = $request->input('source');

        $lesson->fill([
            'chapter_id' => $request->integer('chapter_id'),
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'source' => $source,
            'is_published' => $request->boolean('is_published'),
        ]);

        $staleVideo = null;
        $banner = null;

        if ($source === Lesson::SOURCE_UPLOAD) {
            if ($request->hasFile('video')) {
                $lesson->video_path = Uploads::store($request->file('video'), 'videos');
                $staleVideo = $oldVideoPath;             // replaced with a new file
            } else {
                $lesson->video_path = $oldVideoPath;     // keep what is already there
            }
        } else {
            $lesson->youtube_id = $request->youtubeId();
            $staleVideo = $oldVideoPath;                 // switched from upload to YouTube

            // Re-verify ownership: the video (or the teacher's connected channels) may have changed.
            [$attribution, $banner] = $this->attribute($ownership, $request->youtubeId(), $request->user());
            $lesson->fill($attribution);
        }

        $staleThumbnail = null;

        if ($request->hasFile('thumbnail')) {
            $lesson->thumbnail_path = Uploads::store($request->file('thumbnail'), 'thumbnails');
            $staleThumbnail = $oldThumbnailPath;
        }

        $lesson->save();

        // Only after the row is safely saved do we drop the files it no longer points at.
        foreach (array_filter([$staleVideo, $staleThumbnail]) as $path) {
            Storage::disk('uploads')->delete($path);
        }

        return $this->savedRedirect(
            __('Video ":title" berjaya dikemas kini.', ['title' => $lesson->title]),
            $banner,
        );
    }

    /**
     * Attribute a YouTube video to the teacher. Blocks the save when the video is private/deleted;
     * otherwise returns the ownership columns to persist plus an optional non-blocking banner.
     *
     * @return array{0: array{ownership:string, counts_for_talent:bool, youtube_channel_id:?string}, 1: ?string}
     *
     * @throws ValidationException
     */
    private function attribute(OwnershipService $ownership, string $youtubeId, User $teacher): array
    {
        $result = $ownership->attributeYoutube($youtubeId, $teacher);

        if ($result['status'] === OwnershipService::STATUS_BLOCKED) {
            throw ValidationException::withMessages([
                'youtube_url' => __('Video ini peribadi atau telah dipadam. Sila tetapkan ke Unlisted atau Public.'),
            ]);
        }

        $banner = match ($result['status']) {
            OwnershipService::STATUS_REFERENCE => $teacher->youtubeChannels()->exists()
                ? __('Video ini bukan dari channel YouTube anda, jadi ia tidak dikira untuk skor bakat anda. Ia masih boleh ditonton murid.')
                : __('Ini video anda sendiri? Sambungkan akaun YouTube anda supaya ia dikira untuk skor bakat anda.'),
            OwnershipService::STATUS_DEFERRED => __('Pengesahan pemilikan video tertangguh (masalah rangkaian sementara). Ia akan disemak semula apabila anda menyunting video ini.'),
            default => null,
        };

        return [[
            'ownership' => $result['ownership'],
            'counts_for_talent' => $result['counts_for_talent'],
            'youtube_channel_id' => $result['youtube_channel_id'],
        ], $banner];
    }

    private function savedRedirect(string $status, ?string $banner): RedirectResponse
    {
        $redirect = redirect()->route('cikgu.video.index')->with('status', $status);

        return $banner ? $redirect->with('info', $banner) : $redirect;
    }

    public function destroy(Lesson $lesson): RedirectResponse
    {
        $this->authorize('delete', $lesson);

        $title = $lesson->title;

        $lesson->deleteFiles();
        $lesson->delete();

        return redirect()
            ->route('cikgu.video.index')
            ->with('status', __('Video ":title" telah dipadam.', ['title' => $title]));
    }

    /**
     * Publish toggle from the lesson list. A POST, not a GET: it changes state.
     */
    public function togglePublish(Lesson $lesson): RedirectResponse
    {
        $this->authorize('update', $lesson);

        $lesson->update(['is_published' => ! $lesson->is_published]);

        return back()->with('status', $lesson->is_published
            ? __('Video telah diterbitkan. Murid boleh menontonnya sekarang.')
            : __('Video telah disembunyikan daripada murid.'));
    }
}
