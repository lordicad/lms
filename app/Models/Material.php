<?php

namespace App\Models;

use Database\Factories\MaterialFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use App\Support\MediaUrl;

class Material extends Model
{
    /** @use HasFactory<MaterialFactory> */
    use HasFactory;

    protected $fillable = [
        'chapter_id',
        'lesson_id',
        'teacher_id',
        'title',
        'file_path',
        'original_name',
        'mime_type',
        'size_kb',
    ];

    protected function casts(): array
    {
        return [
            'chapter_id' => 'integer',
            'lesson_id' => 'integer',
            'teacher_id' => 'integer',
            'size_kb' => 'integer',
            'download_count' => 'integer',
        ];
    }

    /**
     * What a student may see and download: materials in a current (active) chapter that are not
     * attached to a video still in draft. Materials have no publish flag of their own, so a file
     * attached to a draft lesson used to be listed and downloadable before the lesson went live.
     * One rule for every student-facing list and the download check, so they cannot disagree.
     */
    public function scopeVisibleToStudents(Builder $query): Builder
    {
        return $query
            ->whereHas('chapter', fn (Builder $chapter) => $chapter->where('is_active', true))
            ->where(fn (Builder $q) => $q
                ->whereNull('lesson_id')
                ->orWhereHas('lesson', fn (Builder $lesson) => $lesson->where('is_published', true)));
    }

    public function isVisibleToStudents(): bool
    {
        return static::whereKey($this->id)->visibleToStudents()->exists();
    }

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function extension(): string
    {
        return strtolower(pathinfo($this->original_name, PATHINFO_EXTENSION));
    }

    /**
     * Emoji file-type icon. Kept for the teacher UI; the student surface uses iconName() vectors.
     */
    public function icon(): string
    {
        return match ($this->extension()) {
            'pdf' => '📕',
            'ppt', 'pptx' => '📊',
            'doc', 'docx' => '📝',
            'xls', 'xlsx' => '📈',
            'png', 'jpg', 'jpeg' => '🖼️',
            default => '📄',
        };
    }

    /**
     * Vector (Lucide/Tabler) icon name for the file type - used on the student surface.
     */
    public function iconName(): string
    {
        return match ($this->extension()) {
            'pdf' => 'file-pdf',
            'ppt', 'pptx' => 'presentation',
            'doc', 'docx' => 'file-text',
            'xls', 'xlsx' => 'table',
            'png', 'jpg', 'jpeg' => 'photo',
            default => 'file',
        };
    }

    /**
     * Direct URL to the stored file. The download route is the one students use - it returns the
     * original filename and counts the download - so this is only for showing a file in place,
     * where a forced attachment would defeat the point.
     */
    public function fileUrl(): ?string
    {
        // Signed and short-lived: the materials folder is closed to direct web access.
        return $this->file_path
            ? MediaUrl::for('media.material', $this)
            : null;
    }

    /**
     * What a browser can actually show inline: PDFs render natively and images are images.
     * Word, PowerPoint and Excel cannot be displayed without shipping the file to a third-party
     * viewer, so they report 'none' and the UI offers the download instead of faking a preview.
     */
    public function previewKind(): string
    {
        return match ($this->extension()) {
            'pdf' => 'pdf',
            'png', 'jpg', 'jpeg' => 'image',
            default => 'none',
        };
    }

    public function humanSize(): string
    {
        if ($this->size_kb >= 1024) {
            return number_format($this->size_kb / 1024, 1).' MB';
        }

        return $this->size_kb.' KB';
    }

    public function deleteFile(): void
    {
        Storage::disk('uploads')->delete($this->file_path);
    }
}
