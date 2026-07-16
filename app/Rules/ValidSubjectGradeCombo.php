<?php

namespace App\Rules;

use App\Models\Chapter;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

/**
 * Guards the Kurikulum 2027 availability map wherever a teacher chooses where content lives.
 *
 * Two modes:
 *   - chapter (default): the attribute is a chapter_id. The chapter must still be active and its
 *     (subject, Tahun) must be an offered pair — this is how new content is kept out of chapters
 *     that left the syllabus.
 *   - pair: the attribute pairs with a sibling field (subject_id + grade_id, or subject + grade),
 *     used when a raw (subject, Tahun) is chosen — e.g. adding a Bab.
 */
class ValidSubjectGradeCombo implements DataAwareRule, ValidationRule
{
    /** @var array<string, mixed> */
    protected array $data = [];

    public function __construct(
        private readonly string $mode = 'chapter',
        private readonly string $subjectField = 'subject_id',
        private readonly string $gradeField = 'grade_id',
    ) {}

    public static function forChapter(): self
    {
        return new self('chapter');
    }

    public static function forPair(string $subjectField = 'subject_id', string $gradeField = 'grade_id'): self
    {
        return new self('pair', $subjectField, $gradeField);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->mode === 'chapter') {
            $chapter = Chapter::find($value);

            // A missing chapter is the 'exists' rule's job to report, not ours.
            if (! $chapter) {
                return;
            }

            if (! $chapter->is_active) {
                $fail(__('Bab ini tidak lagi dalam Kurikulum 2027. Sila pindahkan kandungan ke Bab lain.'));

                return;
            }

            $subjectId = $chapter->subject_id;
            $gradeId = $chapter->grade_id;
        } else {
            $subjectId = $this->data[$this->subjectField] ?? null;
            $gradeId = $this->data[$this->gradeField] ?? null;

            // Let 'required'/'exists' on the sibling fields report anything missing.
            if (! $subjectId || ! $gradeId) {
                return;
            }
        }

        if (! $this->isOffered((int) $subjectId, (int) $gradeId)) {
            $fail(__('Subjek ini tiada untuk Tahun tersebut dalam Kurikulum 2027.'));
        }
    }

    private function isOffered(int $subjectId, int $gradeId): bool
    {
        return DB::table('grade_subject')
            ->where('subject_id', $subjectId)
            ->where('grade_id', $gradeId)
            ->exists();
    }
}
