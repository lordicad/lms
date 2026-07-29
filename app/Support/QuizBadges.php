<?php

namespace App\Support;

/**
 * The catalogue of quiz badges: what can be earned, the score thresholds behind them, and the
 * copy + colours each one is drawn with. Badges are never stored — BadgeService derives them from
 * quiz_attempts on read — so this file is the single source of truth for what a badge *is*.
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
     * Copy + visual style for one badge key.
     *
     * @return array{label:string, desc:string, icon:string, bg:string, ring:string, ink:string}
     */
    public static function meta(string $key): array
    {
        return match ($key) {
            'completed' => ['label' => __('Kuiz Selesai'), 'desc' => __('Menyelesaikan kuiz'), 'icon' => 'check-circle', 'bg' => 'linear-gradient(160deg,#DCF2EE,#C6E9DF)', 'ring' => '#17907B', 'ink' => '#0F7A68'],
            'bronze' => ['label' => __('Gangsa'), 'desc' => __('Skor 70% ke atas'), 'icon' => 'star', 'bg' => 'linear-gradient(160deg,#F4E2CD,#E7CDAC)', 'ring' => '#B87333', 'ink' => '#8A5A2B'],
            'silver' => ['label' => __('Perak'), 'desc' => __('Skor 80% ke atas'), 'icon' => 'star', 'bg' => 'linear-gradient(160deg,#EEF0F4,#D9DEE6)', 'ring' => '#98A0AC', 'ink' => '#5F6875'],
            'gold' => ['label' => __('Emas'), 'desc' => __('Skor 90% ke atas'), 'icon' => 'star', 'bg' => 'linear-gradient(160deg,#FBEFC7,#F5DA8E)', 'ring' => '#E0A21C', 'ink' => '#956409'],
            'perfect' => ['label' => __('Sempurna'), 'desc' => __('Skor 100%'), 'icon' => 'crown', 'bg' => 'linear-gradient(160deg,#EEE6FB,#DBCBF5)', 'ring' => '#7C5CBF', 'ink' => '#5B3E9E'],
            'never_give_up' => ['label' => __('Pantang Menyerah'), 'desc' => __('3+ percubaan pada kuiz sama'), 'icon' => 'flame', 'bg' => 'linear-gradient(160deg,#FDE7DE,#F8CDBB)', 'ring' => '#E5733D', 'ink' => '#C24936'],
            'comeback' => ['label' => __('Bangkit Semula'), 'desc' => __('Lulus selepas gagal'), 'icon' => 'trending-up', 'bg' => 'linear-gradient(160deg,#DCEBF8,#C3DCF3)', 'ring' => '#3E86C9', 'ink' => '#2E6CA8'],
            default => ['label' => $key, 'desc' => '', 'icon' => 'check', 'bg' => '#EEEEEE', 'ring' => '#CCCCCC', 'ink' => '#555555'],
        };
    }
}
