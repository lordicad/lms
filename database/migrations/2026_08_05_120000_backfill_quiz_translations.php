<?php

use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Database\Migrations\Migration;

/**
 * Backfill translations for the quizzes that existed before the auto-translate-on-create feature
 * had an API key. Every quiz on file is in Bahasa Melayu, so each is marked source_locale = 'ms'
 * and given its English counterpart; a reader on the other language then sees the translation via
 * the model's localized*() helpers. Future quizzes translate themselves at creation once
 * ANTHROPIC_API_KEY is set on the server.
 *
 * Records are matched by their stored text rather than id, so this applies cleanly to whichever
 * database it runs against; a quiz whose text is not listed here is simply left untouched.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Quizzes: title => [title_en, description_ms (or null), description_en (or null)].
        $quizzes = [
            'Kuiz Ringkas: Bahagian Badan' => [
                'Quick Quiz: Body Parts',
                'Lima soalan pendek untuk menguji kefahaman anda.',
                'Five short questions to test your understanding.',
            ],
            'Kuiz Cetak: Latihan Tambahan' => [
                'Print Quiz: Extra Practice', null, null,
            ],
        ];

        foreach ($quizzes as $title => [$titleEn, $descMs, $descEn]) {
            Quiz::where('title', $title)->get()->each(function (Quiz $quiz) use ($titleEn, $descMs, $descEn) {
                $quiz->source_locale = 'ms';
                $quiz->title_translated = $titleEn;
                if ($quiz->description && $descMs && $quiz->description === $descMs) {
                    $quiz->description_translated = $descEn;
                }
                $quiz->save();
            });
        }

        // Questions: question_text => [question_en, [option_ms => option_en, ...]].
        $questions = [
            'Organ manakah yang mengepam darah ke seluruh badan?' => [
                'Which organ pumps blood throughout the body?',
                ['Jantung' => 'Heart', 'Paru-paru' => 'Lungs', 'Hati' => 'Liver', 'Ginjal' => 'Kidney'],
            ],
            'Yang manakah antara berikut adalah organ deria? (pilih semua yang betul)' => [
                'Which of the following are sensory organs? (select all that apply)',
                ['Mata' => 'Eyes', 'Telinga' => 'Ears', 'Tulang' => 'Bones', 'Kulit' => 'Skin'],
            ],
            'Berapakah bilangan deria manusia?' => [
                'How many senses do humans have?',
                ['Tiga' => 'Three', 'Lima' => 'Five', 'Tujuh' => 'Seven', 'Sembilan' => 'Nine'],
            ],
            'Kita bernafas menggunakan organ yang manakah?' => [
                'Which organ do we use to breathe?',
                ['Perut' => 'Stomach', 'Paru-paru' => 'Lungs', 'Jantung' => 'Heart', 'Otak' => 'Brain'],
            ],
            'Yang manakah membantu kita bergerak? (pilih semua yang betul)' => [
                'Which of these help us move? (select all that apply)',
                ['Otot' => 'Muscles', 'Tulang' => 'Bones', 'Rambut' => 'Hair', 'Kuku' => 'Nails'],
            ],
        ];

        foreach ($questions as $text => [$questionEn, $optionMap]) {
            Question::where('question_text', $text)->with('options')->get()->each(function (Question $question) use ($questionEn, $optionMap) {
                $question->source_locale = 'ms';
                $question->question_text_translated = $questionEn;
                $question->save();

                foreach ($question->options as $option) {
                    if (isset($optionMap[$option->option_text])) {
                        $option->option_text_translated = $optionMap[$option->option_text];
                        $option->save();
                    }
                }
            });
        }
    }

    public function down(): void
    {
        // Clear only what this migration set, by the same text match.
        foreach (['Kuiz Ringkas: Bahagian Badan', 'Kuiz Cetak: Latihan Tambahan'] as $title) {
            Quiz::where('title', $title)->get()->each(function (Quiz $quiz) {
                $quiz->forceFill(['source_locale' => null, 'title_translated' => null, 'description_translated' => null])->save();
            });
        }

        foreach ([
            'Organ manakah yang mengepam darah ke seluruh badan?',
            'Yang manakah antara berikut adalah organ deria? (pilih semua yang betul)',
            'Berapakah bilangan deria manusia?',
            'Kita bernafas menggunakan organ yang manakah?',
            'Yang manakah membantu kita bergerak? (pilih semua yang betul)',
        ] as $text) {
            Question::where('question_text', $text)->with('options')->get()->each(function (Question $question) {
                $question->forceFill(['source_locale' => null, 'question_text_translated' => null])->save();
                foreach ($question->options as $option) {
                    $option->forceFill(['option_text_translated' => null])->save();
                }
            });
        }
    }
};
