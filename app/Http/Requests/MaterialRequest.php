<?php

namespace App\Http\Requests;

use App\Rules\ValidSubjectGradeCombo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MaterialRequest extends FormRequest
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
        $max = config('lms.material_max_mb') * 1024;
        $isCreate = $this->isMethod('POST');

        return [
            'chapter_id' => ['required', 'integer', Rule::exists('chapters', 'id'), ValidSubjectGradeCombo::forChapter()],
            'lesson_id' => ['nullable', 'integer', Rule::exists('lessons', 'id')],
            'title' => ['required', 'string', 'max:255'],
            'file' => [
                Rule::requiredIf($isCreate),
                'nullable',
                'file',
                'mimes:'.implode(',', config('lms.material_mimes')),
                "max:{$max}",
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $max = config('lms.material_max_mb');

        return [
            'chapter_id.required' => __('Sila pilih Subjek, Tahun dan Bab.'),
            'title.required' => __('Sila isi tajuk bahan.'),
            'file.required' => __('Sila pilih fail untuk dimuat naik.'),
            'file.mimes' => __('Format fail tidak dibenarkan. Guna PDF, PowerPoint, Word, Excel atau imej.'),
            'file.max' => __('Saiz fail terlalu besar. Had ialah :max MB.', ['max' => $max]),
        ];
    }
}
