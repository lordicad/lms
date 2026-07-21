<?php

namespace Database\Seeders;

use App\Models\AttemptAnswer;
use App\Models\Chapter;
use App\Models\Grade;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Local demo data so the leaderboard, watch page and quiz flow have something to render.
 * Never runs in production (guarded in DatabaseSeeder).
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $teacher = User::updateOrCreate(
            ['username' => 'cikgu.demo'],
            [
                'name' => 'Cikgu Rahimah Yusof',
                'email' => 'cikgu.demo@lms-moe.test',
                'password' => Hash::make('password'),
                'role' => User::ROLE_TEACHER,
                'grade_id' => null,
            ],
        );

        // Demo accounts exist to be signed into directly, so they are not sent to the
        // first-password screen the way a real admin-created account would be.
        $teacher->markPasswordChanged();

        $students = [
            ['username' => 'aisyah', 'name' => 'Nur Aisyah Rahim', 'level' => 3],
            ['username' => 'harith', 'name' => 'Muhammad Harith Danial', 'level' => 3],
            ['username' => 'meiling', 'name' => 'Tan Mei Ling', 'level' => 4],
        ];

        $studentModels = [];

        foreach ($students as $student) {
            $grade = Grade::where('level', $student['level'])->firstOrFail();

            $studentModels[$student['username']] = User::updateOrCreate(
                ['username' => $student['username']],
                [
                    'name' => $student['name'],
                    'email' => null,
                    'password' => Hash::make('password'),
                    'role' => User::ROLE_STUDENT,
                    'grade_id' => $grade->id,
                ],
            );

            $studentModels[$student['username']]->markPasswordChanged();
        }

        // Sains is a Tahun 5–6 subject under Kurikulum 2027, but the demo students are Tahun 3.
        // The Tahap II integrated science subject, Eksplorasi Sains dan Teknologi, is the valid
        // (subject, Tahun) home for this science content — so it appears on their dashboards.
        $sains = Subject::where('short_name', 'Eksplorasi Sains dan Teknologi')->firstOrFail();
        $tahun3 = Grade::where('level', 3)->firstOrFail();

        $chapter = Chapter::where('subject_id', $sains->id)
            ->where('grade_id', $tahun3->id)
            ->where('number', 1)
            ->firstOrFail();

        $chapter->update(['title' => 'Sains Hayat: Manusia', 'created_by' => $teacher->id]);

        // A real, public, freely-embeddable science clip. Photosynthesis, in the syllabus.
        $lesson = Lesson::updateOrCreate(
            ['chapter_id' => $chapter->id, 'title' => 'Bahagian Badan Manusia'],
            [
                'teacher_id' => $teacher->id,
                'description' => 'Video pengenalan tentang bahagian badan manusia dan fungsinya. Tonton sampai habis, kemudian cuba kuiz di bawah.',
                'source' => Lesson::SOURCE_YOUTUBE,
                'youtube_id' => 'QG1eOFyC4Ho',
                'is_published' => true,
            ],
        );

        $quiz = Quiz::updateOrCreate(
            ['chapter_id' => $chapter->id, 'title' => 'Kuiz Ringkas: Bahagian Badan'],
            [
                'teacher_id' => $teacher->id,
                'description' => 'Lima soalan pendek untuk menguji kefahaman anda.',
                'type' => Quiz::TYPE_INTERACTIVE,
                'duration_minutes' => 10,
                'is_published' => true,
            ],
        );

        // Rebuild the question set so re-seeding never stacks duplicates.
        $quiz->questions()->delete();

        $questions = [
            [
                'text' => 'Organ manakah yang mengepam darah ke seluruh badan?',
                'type' => Question::TYPE_SINGLE,
                'options' => [['Jantung', true], ['Paru-paru', false], ['Hati', false], ['Ginjal', false]],
            ],
            [
                'text' => 'Yang manakah antara berikut adalah organ deria? (pilih semua yang betul)',
                'type' => Question::TYPE_MULTIPLE,
                'options' => [['Mata', true], ['Telinga', true], ['Tulang', false], ['Kulit', true]],
            ],
            [
                'text' => 'Berapakah bilangan deria manusia?',
                'type' => Question::TYPE_SINGLE,
                'options' => [['Tiga', false], ['Lima', true], ['Tujuh', false], ['Sembilan', false]],
            ],
            [
                'text' => 'Kita bernafas menggunakan organ yang manakah?',
                'type' => Question::TYPE_SINGLE,
                'options' => [['Perut', false], ['Paru-paru', true], ['Jantung', false], ['Otak', false]],
            ],
            [
                'text' => 'Yang manakah membantu kita bergerak? (pilih semua yang betul)',
                'type' => Question::TYPE_MULTIPLE,
                'options' => [['Otot', true], ['Tulang', true], ['Rambut', false], ['Kuku', false]],
            ],
        ];

        foreach ($questions as $index => $data) {
            $question = Question::create([
                'quiz_id' => $quiz->id,
                'question_text' => $data['text'],
                'question_type' => $data['type'],
                'points' => 10,
                'sort_order' => $index,
            ]);

            foreach ($data['options'] as $optionIndex => [$text, $correct]) {
                QuestionOption::create([
                    'question_id' => $question->id,
                    'option_text' => $text,
                    'is_correct' => $correct,
                    'sort_order' => $optionIndex,
                ]);
            }
        }

        // Two Tahun 3 students attempt it, so the leaderboard has a real ordering to show.
        $quiz->load('questions.options');

        $this->recordAttempt($quiz, $studentModels['aisyah'], correctRatio: 1.0);
        $this->recordAttempt($quiz, $studentModels['harith'], correctRatio: 0.6);
    }

    /**
     * Grades a demo attempt the same way the real flow does, so the seeded numbers are
     * internally consistent with LeaderboardService.
     */
    private function recordAttempt(Quiz $quiz, User $student, float $correctRatio): void
    {
        QuizAttempt::where('quiz_id', $quiz->id)->where('student_id', $student->id)->delete();

        $questions = $quiz->questions;
        $answerCorrectlyUpTo = (int) round($questions->count() * $correctRatio);

        $attempt = QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'student_id' => $student->id,
            'score' => 0,
            'max_score' => (int) $questions->sum('points'),
            'correct_count' => 0,
            'question_count' => $questions->count(),
            'counts_for_ranking' => true,   // first attempt
            'started_at' => now()->subMinutes(9),
            'completed_at' => now()->subMinutes(3),
            'duration_seconds' => 360,
        ]);

        $score = 0;
        $correctCount = 0;

        foreach ($questions->values() as $index => $question) {
            $shouldBeCorrect = $index < $answerCorrectlyUpTo;

            $selected = $shouldBeCorrect
                ? $question->correctOptionIds()
                : [$question->options->firstWhere('is_correct', false)?->id ?? $question->options->first()->id];

            $isCorrect = $question->isAnswerCorrect($selected);
            $points = $isCorrect ? $question->points : 0;

            AttemptAnswer::create([
                'quiz_attempt_id' => $attempt->id,
                'question_id' => $question->id,
                'selected_option_ids' => array_values($selected),
                'is_correct' => $isCorrect,
                'points_awarded' => $points,
            ]);

            $score += $points;
            $correctCount += $isCorrect ? 1 : 0;
        }

        $attempt->update(['score' => $score, 'correct_count' => $correctCount]);
    }
}
