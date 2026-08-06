<?php

namespace App\Http\Controllers\Cikgu;

use App\Http\Controllers\Controller;
use App\Http\Requests\QuizRequest;
use App\Models\Grade;
use App\Models\Quiz;
use App\Models\Subject;
use App\Services\QuestionTranslator;
use App\Support\Uploads;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Throwable;

class QuizController extends Controller
{
    public function index(Request $request): View
    {
        $teacher = $request->user();

        // Discard abandoned drafts. An interactive quiz needs at least one question to be usable;
        // a teacher who created one and left the builder without adding any - via "Batal", the
        // "← Kuiz Saya" link, the nav, or the browser back button - should not be left with an
        // empty quiz. Cleaning up here catches every exit path that lands back on this page.
        // Scoped to interactive quizzes so printed (file) quizzes, which never have questions,
        // are untouched.
        $teacher->quizzes()
            ->where('type', Quiz::TYPE_INTERACTIVE)
            ->whereDoesntHave('questions')
            ->delete();

        $filter = \App\Support\ContentFilter::fromRequest($request);

        $quizzes = $filter->apply(
            $teacher->quizzes()
                ->with('chapter.subject', 'chapter.grade', 'questions.options')
                ->withCount(['questions', 'attempts as completed_attempts_count' => fn ($q) => $q->whereNotNull('completed_at')])
        )
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        return view('cikgu.kuiz.index', [
            'quizzes' => $quizzes,
            'subjects' => Subject::orderBy('sort_order')->get(),
            'grades' => Grade::orderBy('level')->get(),
            'filter' => $filter,
            // All-time count of quizzes created by this teacher (not the filtered page count).
            'totalQuizzes' => $teacher->quizzes()->count(),
        ]);
    }

    /**
     * Step 1 of creating a quiz: choose between a printable file and a built-in MCQ quiz.
     */
    public function mode(): View
    {
        $this->authorize('create', Quiz::class);

        return view('cikgu.kuiz.mod');
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Quiz::class);

        $type = $request->string('jenis')->toString();

        if (! in_array($type, [Quiz::TYPE_FILE, Quiz::TYPE_INTERACTIVE], true)) {
            $type = Quiz::TYPE_INTERACTIVE;
        }

        return view('cikgu.kuiz.form', [
            'quiz' => new Quiz(['type' => $type, 'is_published' => true]),
            'subjects' => Subject::orderBy('sort_order')->get(),
            'grades' => Grade::orderBy('level')->get(),
            'chapter' => null,
            'translatorEnabled' => app(QuestionTranslator::class)->enabled(),
        ]);
    }

    public function store(QuizRequest $request): RedirectResponse
    {
        $this->authorize('create', Quiz::class);

        // A printed quiz can be a batch: each file becomes its own quiz, the same way materials
        // and videos upload many at once.
        if ($request->input('type') === Quiz::TYPE_FILE) {
            return $this->storeFileBatch($request);
        }

        $quiz = Quiz::create([
            'chapter_id' => $request->integer('chapter_id'),
            'teacher_id' => $request->user()->id,
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'type' => Quiz::TYPE_INTERACTIVE,
            'is_published' => $request->boolean('is_published'),
            'duration_minutes' => $request->input('duration_minutes') ?: null,
            'shuffle_options' => $request->boolean('shuffle_options'),
            ...$this->metaTranslation($request),
        ]);

        // An interactive quiz is not usable until it has questions, so go straight there.
        return redirect()
            ->route('cikgu.kuiz.soalan', $quiz)
            ->with('status', __('Kuiz dicipta. Sekarang tambah soalan.'));
    }

    /**
     * One printed quiz per uploaded file. Titles pair to files by position; a blank one falls back
     * to the file's own name, and the shared description/publish flag apply to the whole batch.
     */
    private function storeFileBatch(QuizRequest $request): RedirectResponse
    {
        $titles = $request->input('titles', []);
        $files = $request->file('files', []);
        $chapterId = $request->integer('chapter_id');
        $description = $request->input('description');
        $isPublished = $request->boolean('is_published');

        $created = [];

        foreach ($files as $index => $file) {
            $given = trim((string) ($titles[$index] ?? ''));

            $created[] = Quiz::create([
                'chapter_id' => $chapterId,
                'teacher_id' => $request->user()->id,
                'title' => $given !== '' ? $given : pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'description' => $description,
                'type' => Quiz::TYPE_FILE,
                'is_published' => $isPublished,
                'duration_minutes' => null,
                'file_path' => Uploads::store($file, 'quizzes'),
                'original_name' => $file->getClientOriginalName(),
            ]);
        }

        $status = count($created) === 1
            ? __('Kuiz ":title" berjaya dimuat naik.', ['title' => $created[0]->title])
            : __(':count kuiz berjaya dimuat naik.', ['count' => count($created)]);

        return redirect()->route('cikgu.kuiz.index')->with('status', $status);
    }

    public function edit(Quiz $quiz): View
    {
        $this->authorize('update', $quiz);

        $quiz->load('chapter.subject', 'chapter.grade');

        return view('cikgu.kuiz.form', [
            'quiz' => $quiz,
            'subjects' => Subject::orderBy('sort_order')->get(),
            'grades' => Grade::orderBy('level')->get(),
            'chapter' => $quiz->chapter,
            'hasAttempts' => $quiz->hasAttempts(),
            'translatorEnabled' => app(QuestionTranslator::class)->enabled(),
        ]);
    }

    public function update(QuizRequest $request, Quiz $quiz): RedirectResponse
    {
        $this->authorize('update', $quiz);

        $oldPath = $quiz->file_path;
        $type = $request->input('type');

        $quiz->fill([
            'chapter_id' => $request->integer('chapter_id'),
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'type' => $type,
            'is_published' => $request->boolean('is_published'),
            'duration_minutes' => $type === Quiz::TYPE_INTERACTIVE
                ? ($request->input('duration_minutes') ?: null)
                : null,
            'shuffle_options' => $type === Quiz::TYPE_INTERACTIVE && $request->boolean('shuffle_options'),
            ...$this->metaTranslation($request),
        ]);

        $staleFile = null;

        if ($type === Quiz::TYPE_FILE && $request->hasFile('file')) {
            $file = $request->file('file');
            $quiz->file_path = Uploads::store($file, 'quizzes');
            $quiz->original_name = $file->getClientOriginalName();
            $staleFile = $oldPath;
        }

        // Switching a file quiz over to interactive leaves the old document behind.
        if ($type === Quiz::TYPE_INTERACTIVE && $oldPath) {
            $quiz->file_path = null;
            $quiz->original_name = null;
            $staleFile = $oldPath;
        }

        $quiz->save();

        if ($staleFile) {
            Storage::disk('uploads')->delete($staleFile);
        }

        return redirect()
            ->route('cikgu.kuiz.index')
            ->with('status', __('Kuiz ":title" berjaya dikemas kini.', ['title' => $quiz->title]));
    }

    public function destroy(Quiz $quiz): RedirectResponse
    {
        $this->authorize('delete', $quiz);

        $title = $quiz->title;

        $quiz->deleteFile();
        $quiz->delete();   // questions, options and attempts cascade

        return redirect()
            ->route('cikgu.kuiz.index')
            ->with('status', __('Kuiz ":title" telah dipadam.', ['title' => $title]));
    }

    /**
     * Live translation for the quiz form's "Terjemah automatik" button: translates the title and
     * description as typed, for the teacher to review and edit before saving.
     */
    public function translateMeta(Request $request, QuestionTranslator $translator): JsonResponse
    {
        $this->authorize('create', Quiz::class);

        if (! $translator->enabled()) {
            return response()->json(['message' => __('Terjemahan automatik tidak tersedia.')], 422);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $hasDescription = filled($validated['description'] ?? null);
        $strings = $hasDescription ? [$validated['title'], $validated['description']] : [$validated['title']];

        try {
            $results = $translator->translateStrings($strings);
        } catch (Throwable $e) {
            Log::warning('Quiz meta live-translate failed', ['error' => $e->getMessage()]);

            return response()->json(['message' => __('Terjemahan gagal. Sila cuba lagi.')], 502);
        }

        return response()->json([
            'source_locale' => $results[0]['source_locale'] ?? 'ms',
            'title' => $results[0]['text'] ?? '',
            'description' => $hasDescription ? ($results[1]['text'] ?? '') : '',
        ]);
    }

    /**
     * The title/description translation fields to save with a quiz. Uses the teacher's reviewed
     * translation from the form when present, otherwise auto-translates on save. Best-effort: any
     * API failure returns nulls so the quiz still saves.
     *
     * @return array{source_locale: ?string, title_translated: ?string, description_translated: ?string}
     */
    private function metaTranslation(Request $request): array
    {
        $none = ['source_locale' => null, 'title_translated' => null, 'description_translated' => null];

        // Teacher reviewed a translation in the form - keep it verbatim.
        $providedLocale = $request->input('source_locale');
        $providedTitle = $request->input('title_translated');

        if (in_array($providedLocale, ['ms', 'en'], true) && filled($providedTitle)) {
            $providedDescription = $request->input('description_translated');

            return [
                'source_locale' => $providedLocale,
                'title_translated' => $providedTitle,
                'description_translated' => filled($providedDescription) ? $providedDescription : null,
            ];
        }

        $title = (string) $request->input('title');
        $description = (string) $request->input('description');
        $translator = app(QuestionTranslator::class);

        if (! $translator->enabled() || trim($title) === '') {
            return $none;
        }

        $hasDescription = trim($description) !== '';
        $strings = $hasDescription ? [$title, $description] : [$title];

        try {
            $results = $translator->translateStrings($strings);
        } catch (Throwable $e) {
            Log::warning('Quiz meta auto-translate on save failed', ['error' => $e->getMessage()]);

            return $none;
        }

        return [
            'source_locale' => $results[0]['source_locale'] ?? null,
            'title_translated' => $results[0]['text'] ?? null,
            'description_translated' => $hasDescription ? ($results[1]['text'] ?? null) : null,
        ];
    }
}
