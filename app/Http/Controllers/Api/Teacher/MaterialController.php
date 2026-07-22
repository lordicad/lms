<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\Material;
use App\Support\Uploads;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Mobile upload and editing for teacher-owned learning materials. */
class MaterialController extends Controller
{
    /** Download a teacher-owned material for the mobile file viewer. */
    public function download(Request $request, Material $material): StreamedResponse|JsonResponse
    {
        $teacher = $this->teacher($request);
        if (! $teacher || $material->teacher_id !== $teacher->id) {
            return $this->forbidden();
        }

        $disk = Storage::disk('uploads');
        abort_unless($material->file_path && $disk->exists($material->file_path), 404);

        return $disk->download($material->file_path, $material->original_name ?: $material->title);
    }

    public function store(Request $request): JsonResponse
    {
        $teacher = $this->teacher($request);
        if (! $teacher) {
            return $this->forbidden();
        }

        $data = $this->validated($request, true);
        $this->ensureLessonBelongsToTeacher($teacher->id, $data);
        $file = $request->file('file');

        $material = Material::create([
            'chapter_id' => $data['chapter_id'],
            'lesson_id' => $data['lesson_id'] ?? null,
            'teacher_id' => $teacher->id,
            'title' => $data['title'],
            'file_path' => Uploads::store($file, 'materials'),
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size_kb' => Uploads::sizeKb($file),
        ]);

        return response()->json(['id' => $material->id], 201);
    }

    public function update(Request $request, Material $material): JsonResponse
    {
        $teacher = $this->teacher($request);
        if (! $teacher || $material->teacher_id !== $teacher->id) {
            return $this->forbidden();
        }

        $data = $this->validated($request, false);
        $this->ensureLessonBelongsToTeacher($teacher->id, $data);
        $oldPath = $material->file_path;

        $material->fill([
            'chapter_id' => $data['chapter_id'],
            'lesson_id' => $data['lesson_id'] ?? null,
            'title' => $data['title'],
        ]);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $material->fill([
                'file_path' => Uploads::store($file, 'materials'),
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size_kb' => Uploads::sizeKb($file),
            ]);
        }

        $material->save();

        if ($request->hasFile('file')) {
            Storage::disk('uploads')->delete($oldPath);
        }

        return response()->json(['id' => $material->id]);
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, bool $creating): array
    {
        $max = config('lms.material_max_mb') * 1024;

        return $request->validate([
            'chapter_id' => ['required', 'integer', Rule::exists('chapters', 'id')],
            'lesson_id' => ['nullable', 'integer', Rule::exists('lessons', 'id')],
            'title' => ['required', 'string', 'max:255'],
            'file' => [
                Rule::requiredIf($creating),
                'nullable',
                'file',
                'mimes:'.implode(',', config('lms.material_mimes')),
                "max:{$max}",
            ],
        ], [
            'chapter_id.required' => __('Sila pilih Subjek, Tahun dan Bab.'),
            'title.required' => __('Sila isi tajuk bahan.'),
            'file.required' => __('Sila pilih fail untuk dimuat naik.'),
            'file.mimes' => __('Format fail tidak dibenarkan. Guna PDF, PowerPoint, Word, Excel atau imej.'),
            'file.max' => __('Saiz fail terlalu besar. Had ialah :max MB.', ['max' => config('lms.material_max_mb')]),
        ]);
    }

    /** @param array<string, mixed> $data */
    private function ensureLessonBelongsToTeacher(int $teacherId, array $data): void
    {
        if (empty($data['lesson_id'])) {
            return;
        }

        $valid = Lesson::query()
            ->whereKey($data['lesson_id'])
            ->where('teacher_id', $teacherId)
            ->where('chapter_id', $data['chapter_id'])
            ->exists();

        abort_unless($valid, 422, __('Video yang dipilih tidak sah untuk bab ini.'));
    }

    private function teacher(Request $request): ?\App\Models\User
    {
        $user = $request->user();

        return $user && $user->isTeacher() ? $user : null;
    }

    private function forbidden(): JsonResponse
    {
        return response()->json(['message' => 'Hanya guru boleh mengakses ini.'], 403);
    }
}
