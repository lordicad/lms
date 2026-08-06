<?php

namespace App\Http\Requests;

use App\Models\Quiz;
use App\Rules\ValidSubjectGradeCombo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuizRequest extends FormRequest
{
    /** A printed quiz upload takes a batch, one quiz per file - the same ceiling as materials. */
    public const MAX_FILES = 20;

    public function authorize(): bool
    {
        return $this->user()?->isTeacher() ?? false;
    }

    /**
     * The three shapes a quiz form can take:
     *   - interactive (create or edit): one quiz, a shared title, an optional time limit;
     *   - printed, creating: any number of files, each becoming its own quiz, titled per row;
     *   - printed, editing: a single replacement file for the one quiz being edited.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $max = config('lms.quiz_file_max_mb') * 1024;
        $mimes = 'mimes:'.implode(',', config('lms.quiz_file_mimes'));

        $shared = [
            'chapter_id' => ['required', 'integer', Rule::exists('chapters', 'id'), ValidSubjectGradeCombo::forChapter()],
            'description' => ['nullable', 'string', 'max:5000'],
            'type' => ['required', Rule::in([Quiz::TYPE_FILE, Quiz::TYPE_INTERACTIVE])],
            'is_published' => ['boolean'],
        ];

        if ($this->input('type') === Quiz::TYPE_INTERACTIVE) {
            return $shared + [
                'title' => ['required', 'string', 'max:255'],
                'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:180'],
            ];
        }

        // Creating printed quizzes: a batch, each file its own quiz.
        if ($this->isMethod('POST')) {
            return $shared + [
                'files' => ['required', 'array', 'min:1', 'max:'.self::MAX_FILES],
                'files.*' => ['file', $mimes, "max:{$max}"],
                'titles' => ['nullable', 'array', 'max:'.self::MAX_FILES],
                'titles.*' => ['nullable', 'string', 'max:255'],
            ];
        }

        // Editing one printed quiz stays single: the file is optional (blank keeps the current one).
        return $shared + [
            'title' => ['required', 'string', 'max:255'],
            'file' => ['nullable', 'file', $mimes, "max:{$max}"],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $max = config('lms.quiz_file_max_mb');
        $mimeMsg = __('Format fail kuiz mesti PDF, DOC atau DOCX.');
        $sizeMsg = __('Saiz fail terlalu besar. Had ialah :max MB.', ['max' => $max]);

        return [
            'chapter_id.required' => __('Sila pilih Subjek, Tahun dan Bab.'),
            'title.required' => __('Sila isi tajuk kuiz.'),
            'type.required' => __('Sila pilih jenis kuiz.'),
            'file.required' => __('Sila pilih fail kuiz untuk dimuat naik.'),
            'file.mimes' => $mimeMsg,
            'file.max' => $sizeMsg,
            'files.required' => __('Sila pilih fail kuiz untuk dimuat naik.'),
            'files.max' => __('Terlalu banyak fail. Had ialah :max fail sekali muat naik.', ['max' => self::MAX_FILES]),
            'files.*.mimes' => $mimeMsg,
            'files.*.max' => $sizeMsg,
            'titles.*.max' => __('Tajuk terlalu panjang. Had ialah 255 aksara.'),
            'duration_minutes.min' => __('Masa kuiz mesti sekurang-kurangnya 1 minit.'),
            'duration_minutes.max' => __('Masa kuiz tidak boleh melebihi 180 minit.'),
        ];
    }
}
