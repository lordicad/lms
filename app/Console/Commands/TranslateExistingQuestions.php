<?php

namespace App\Console\Commands;

use App\Models\Question;
use App\Services\QuestionTranslator;
use Illuminate\Console\Command;
use Throwable;

/**
 * One-time backfill: translate questions that were created before the auto-translation feature
 * existed (or before an API key was configured). New questions translate on save, so this is only
 * needed once to catch up the existing library. Safe to re-run — it skips anything already done.
 *
 *   php artisan quiz:translate-existing            # translate all questions missing a translation
 *   php artisan quiz:translate-existing --quiz=12  # just one quiz
 */
class TranslateExistingQuestions extends Command
{
    protected $signature = 'quiz:translate-existing {--quiz= : Only this quiz id} {--force : Re-translate even if a translation already exists}';

    protected $description = 'Backfill BM⇄EN translations for existing quiz questions';

    public function handle(QuestionTranslator $translator): int
    {
        if (! $translator->enabled()) {
            $this->error('ANTHROPIC_API_KEY is not set — nothing to do.');

            return self::FAILURE;
        }

        $query = Question::query()->with('options')->orderBy('id');

        if ($quizId = $this->option('quiz')) {
            $query->where('quiz_id', $quizId);
        }

        if (! $this->option('force')) {
            $query->whereNull('question_text_translated');
        }

        $questions = $query->get();

        if ($questions->isEmpty()) {
            $this->info('No questions need translating.');

            return self::SUCCESS;
        }

        $this->info("Translating {$questions->count()} question(s)...");
        $bar = $this->output->createProgressBar($questions->count());

        $done = 0;
        $failed = 0;

        // One API call per question keeps a failure isolated to that question rather than losing a
        // whole batch. Slower, but this is a one-off.
        foreach ($questions as $question) {
            try {
                $result = $translator->translate([[
                    'question' => $question->question_text,
                    'options' => $question->options->pluck('option_text')->all(),
                ]])[0] ?? null;

                if ($result) {
                    $question->update([
                        'source_locale' => $result['source_locale'],
                        'question_text_translated' => $result['question'],
                    ]);

                    foreach ($question->options->values() as $i => $option) {
                        $option->update(['option_text_translated' => $result['options'][$i] ?? null]);
                    }

                    $done++;
                }
            } catch (Throwable $e) {
                $failed++;
                $this->newLine();
                $this->warn("Question {$question->id} failed: {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Done. Translated {$done}, failed {$failed}.");

        return self::SUCCESS;
    }
}
