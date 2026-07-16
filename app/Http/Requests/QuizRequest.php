<?php

namespace App\Http\Requests;

use App\Models\Quiz;
use App\Rules\ValidSubjectGradeCombo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuizRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isTeacher() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $max = config('lms.quiz_file_max_mb') * 1024;

        return [
            'chapter_id' => ['required', 'integer', Rule::exists('chapters', 'id'), ValidSubjectGradeCombo::forChapter()],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'type' => ['required', Rule::in([Quiz::TYPE_FILE, Quiz::TYPE_INTERACTIVE])],

            'file' => [
                Rule::requiredIf($this->needsFile()),
                'nullable',
                'file',
                'mimes:'.implode(',', config('lms.quiz_file_mimes')),
                "max:{$max}",
            ],

            // Only meaningful for interactive quizzes.
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:180'],

            'is_published' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $max = config('lms.quiz_file_max_mb');

        return [
            'chapter_id.required' => __('Sila pilih Subjek, Tahun dan Bab.'),
            'title.required' => __('Sila isi tajuk kuiz.'),
            'type.required' => __('Sila pilih jenis kuiz.'),
            'file.required' => __('Sila pilih fail kuiz untuk dimuat naik.'),
            'file.mimes' => __('Format fail kuiz mesti PDF, DOC atau DOCX.'),
            'file.max' => __('Saiz fail terlalu besar. Had ialah :max MB.', ['max' => $max]),
            'duration_minutes.min' => __('Masa kuiz mesti sekurang-kurangnya 1 minit.'),
            'duration_minutes.max' => __('Masa kuiz tidak boleh melebihi 180 minit.'),
        ];
    }

    private function needsFile(): bool
    {
        if ($this->input('type') !== Quiz::TYPE_FILE) {
            return false;
        }

        $quiz = $this->route('quiz');

        return ! ($quiz instanceof Quiz) || ! $quiz->file_path;
    }
}
