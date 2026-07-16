<?php

namespace App\Http\Requests;

use App\Models\Lesson;
use App\Rules\ValidSubjectGradeCombo;
use App\Support\YouTube;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class LessonRequest extends FormRequest
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
        $videoMax = config('lms.video_max_mb') * 1024;   // validator wants kilobytes

        return [
            'chapter_id' => ['required', 'integer', Rule::exists('chapters', 'id'), ValidSubjectGradeCombo::forChapter()],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'source' => ['required', Rule::in([Lesson::SOURCE_UPLOAD, Lesson::SOURCE_YOUTUBE])],

            'video' => [
                Rule::requiredIf($this->needsVideoFile()),
                'nullable',
                'file',
                'mimes:'.implode(',', config('lms.video_mimes')),
                'mimetypes:'.implode(',', config('lms.video_mimetypes')),
                "max:{$videoMax}",
            ],

            'youtube_url' => [
                Rule::requiredIf($this->input('source') === Lesson::SOURCE_YOUTUBE),
                'nullable',
                'string',
                'max:500',
            ],

            'thumbnail' => [
                'nullable',
                'image',
                'mimes:'.implode(',', config('lms.thumbnail_mimes')),
                'max:4096',
            ],

            'is_published' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $max = config('lms.video_max_mb');

        return [
            'chapter_id.required' => __('Sila pilih Subjek, Tahun dan Bab.'),
            'title.required' => __('Sila isi tajuk video.'),
            'source.required' => __('Sila pilih sumber video.'),
            'video.required' => __('Sila pilih fail video untuk dimuat naik.'),
            'video.mimes' => __('Format video mesti MP4 atau WEBM.'),
            'video.mimetypes' => __('Fail itu bukan video MP4/WEBM yang sah.'),
            'video.max' => __('Saiz video terlalu besar. Had ialah :max MB. Untuk rakaman kelas penuh, sila guna pautan YouTube.', ['max' => $max]),
            'youtube_url.required' => __('Sila tampal pautan YouTube.'),
            'thumbnail.image' => __('Gambar kecil mesti fail imej.'),
            'thumbnail.max' => __('Gambar kecil terlalu besar. Had ialah 4 MB.'),
        ];
    }

    /**
     * The parsed 11-character YouTube id, available to the controller after validation.
     */
    public function youtubeId(): ?string
    {
        return YouTube::parseId($this->input('youtube_url'));
    }

    /**
     * An upload needs a file on create, and on update only when the teacher is switching
     * away from YouTube or replacing the existing file.
     */
    private function needsVideoFile(): bool
    {
        if ($this->input('source') !== Lesson::SOURCE_UPLOAD) {
            return false;
        }

        $lesson = $this->route('lesson');

        return ! ($lesson instanceof Lesson) || ! $lesson->video_path;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->input('source') !== Lesson::SOURCE_YOUTUBE) {
                return;
            }

            if ($this->filled('youtube_url') && ! $this->youtubeId()) {
                $validator->errors()->add(
                    'youtube_url',
                    __('Pautan YouTube tidak sah. Contoh yang betul: https://www.youtube.com/watch?v=xxxxxxxxxxx'),
                );
            }
        });
    }
}
