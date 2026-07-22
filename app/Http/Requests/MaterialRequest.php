<?php

namespace App\Http\Requests;

use App\Rules\ValidSubjectGradeCombo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MaterialRequest extends FormRequest
{
    /** One upload should be a lesson's worth of handouts, not a term's. */
    public const MAX_FILES = 20;

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

        $shared = [
            'chapter_id' => ['required', 'integer', Rule::exists('chapters', 'id'), ValidSubjectGradeCombo::forChapter()],
            'lesson_id' => ['nullable', 'integer', Rule::exists('lessons', 'id')],
        ];

        // Creating takes any number of files at once, each with its own title. Editing stays a
        // single file: it replaces what one material points at, which has no plural meaning.
        if ($isCreate) {
            return $shared + [
                'files' => ['required', 'array', 'min:1', 'max:'.self::MAX_FILES],
                'files.*' => [
                    'file',
                    'mimes:'.implode(',', config('lms.material_mimes')),
                    "max:{$max}",
                ],
                'titles' => ['nullable', 'array', 'max:'.self::MAX_FILES],
                'titles.*' => ['nullable', 'string', 'max:255'],
            ];
        }

        return $shared + [
            'title' => ['required', 'string', 'max:255'],
            'file' => [
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
            'files.required' => __('Sila pilih fail untuk dimuat naik.'),
            'files.max' => __('Terlalu banyak fail. Had ialah :max fail sekali muat naik.', ['max' => self::MAX_FILES]),
            'files.*.mimes' => __('Format fail tidak dibenarkan. Guna PDF, PowerPoint, Word, Excel atau imej.'),
            'files.*.max' => __('Saiz fail terlalu besar. Had ialah :max MB.', ['max' => $max]),
            'titles.*.max' => __('Tajuk terlalu panjang. Had ialah 255 aksara.'),
        ];
    }
}
