<?php

namespace App\Support;

/**
 * The catalogue of quiz badges: what can be earned, the score thresholds behind them, and the
 * copy + colours each one is drawn with. Badges are never stored - BadgeService derives them from
 * quiz_attempts on read - so this file is the single source of truth for what a badge *is*.
 */
class QuizBadges
{
    /** A score at or above this counts as a pass (fail below it). */
    public const PASS = 70;

    /**
     * The one tier badge a first-attempt score earns, highest first. Perfect is an exact full
     * score; the rest go by the rounded percentage the results screen shows.
     */
    public static function tierFor(int $score, int $max): ?string
    {
        if ($max <= 0) {
            return null;
        }

        if ($score >= $max) {
            return 'perfect';
        }

        $percent = (int) round($score / $max * 100);

        return match (true) {
            $percent >= 90 => 'gold',
            $percent >= 80 => 'silver',
            $percent >= 70 => 'bronze',
            default => null,
        };
    }

    public static function passed(int $score, int $max): bool
    {
        return $max > 0 && round($score / $max * 100) >= self::PASS;
    }

    /**
     * Canonical display order for a badge strip or the profile collection.
     *
     * @return list<string>
     */
    public static function order(): array
    {
        return ['completed', 'bronze', 'silver', 'gold', 'perfect', 'never_give_up', 'comeback'];
    }

    /**
     * Account-level milestone badges on the Quizzes page, keyed by how many perfect (100%) scores
     * they take, in order.
     *
     * @return array<string, int>
     */
    public static function milestones(): array
    {
        return [
            'quiz_explorer' => 10,
            'quiz_adventurer' => 25,
            'quiz_expert' => 40,
            'quiz_master' => 60,
        ];
    }

    /**
     * Copy + rosette colours for one badge key. `ribbon` draws the scalloped medal and its tails,
     * `tint` the disc behind the icon, `ring` the inner circle border, `ink` the icon itself.
     *
     * @return array{label:string, desc:string, icon:string, ribbon:string, tint:string, ring:string, ink:string}
     */
    public static function meta(string $key): array
    {
        return match ($key) {
            'completed' => ['label' => __('Kuiz Selesai'), 'desc' => __('Menyelesaikan kuiz'), 'icon' => 'check-circle', 'ribbon' => '#58C4AC', 'tint' => '#DCF2EE', 'ring' => '#23A98F', 'ink' => '#0F7A68'],
            'bronze' => ['label' => __('Gangsa'), 'desc' => __('Skor 70% ke atas'), 'icon' => 'star', 'ribbon' => '#DCA268', 'tint' => '#F5E7D6', 'ring' => '#C0803F', 'ink' => '#8A5A2B'],
            'silver' => ['label' => __('Perak'), 'desc' => __('Skor 80% ke atas'), 'icon' => 'star', 'ribbon' => '#C6CCD6', 'tint' => '#EEF0F4', 'ring' => '#9AA1AD', 'ink' => '#5F6875'],
            'gold' => ['label' => __('Emas'), 'desc' => __('Skor 90% ke atas'), 'icon' => 'star', 'ribbon' => '#F3B94C', 'tint' => '#FEF0CE', 'ring' => '#E0A21C', 'ink' => '#956409'],
            'perfect' => ['label' => __('Sempurna'), 'desc' => __('Skor 100%'), 'icon' => 'crown', 'ribbon' => '#A88FE4', 'tint' => '#E9E4F9', 'ring' => '#7C5CBF', 'ink' => '#5B3E9E'],
            'never_give_up' => ['label' => __('Pantang Menyerah'), 'desc' => __('3+ percubaan pada kuiz sama'), 'icon' => 'flame', 'ribbon' => '#F0885A', 'tint' => '#FDE7DE', 'ring' => '#E5733D', 'ink' => '#C24936'],
            'comeback' => ['label' => __('Bangkit Semula'), 'desc' => __('Lulus selepas gagal'), 'icon' => 'trending-up', 'ribbon' => '#6FA8E0', 'tint' => '#DCEBF8', 'ring' => '#3E86C9', 'ink' => '#2E6CA8'],
            'quiz_explorer' => ['label' => __('Peneroka Kuiz'), 'desc' => __(':count markah penuh', ['count' => 10]), 'icon' => 'rocket', 'ribbon' => '#6FA8E0', 'tint' => '#DCEBF8', 'ring' => '#3E86C9', 'ink' => '#2E6CA8'],
            'quiz_adventurer' => ['label' => __('Pengembara Kuiz'), 'desc' => __(':count markah penuh', ['count' => 25]), 'icon' => 'star', 'ribbon' => '#58C4AC', 'tint' => '#DCF2EE', 'ring' => '#23A98F', 'ink' => '#0F7A68'],
            'quiz_expert' => ['label' => __('Pakar Kuiz'), 'desc' => __(':count markah penuh', ['count' => 40]), 'icon' => 'trophy', 'ribbon' => '#F3B94C', 'tint' => '#FEF0CE', 'ring' => '#E0A21C', 'ink' => '#956409'],
            'quiz_master' => ['label' => __('Sarjana Kuiz'), 'desc' => __(':count markah penuh', ['count' => 60]), 'icon' => 'crown', 'ribbon' => '#A88FE4', 'tint' => '#E9E4F9', 'ring' => '#7C5CBF', 'ink' => '#5B3E9E'],
            default => ['label' => $key, 'desc' => '', 'icon' => 'check', 'ribbon' => '#C9C8D4', 'tint' => '#EDEDF1', 'ring' => '#B9B8C6', 'ink' => '#555555'],
        };
    }
}
