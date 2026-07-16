<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Teacher talent signal
    |--------------------------------------------------------------------------
    |
    | This is a "look closer" signal for MOE, computed from IN-PLATFORM engagement
    | on a teacher's own content (uploads + verified-own YouTube). It is never a
    | verdict on teaching quality, and it never uses YouTube's public view count.
    | Every number here is tunable so the owner can recalibrate without a redeploy
    | of logic. See App\Services\TalentService.
    |
    */

    // Headline "Skor Bakat" = normalised blend of the four visible sub-scores. Must sum to 1.
    'weights' => [
        'engagement' => 0.35,
        'quality'    => 0.25,
        'outcome'    => 0.30,
        'breadth'    => 0.10,
    ],

    // In the engagement sum, a favourite is worth this many views (mirrors TrendingService).
    'favourite_weight' => 3,

    // quality = mean(favourites / reach) over lessons with at least this many distinct watchers.
    // The floor stops a 1-view-1-favourite lesson from reading as a perfect 100%.
    'quality_min_reach' => 5,

    // A single student can contribute at most this many favourites toward one teacher, so no
    // teacher can be inflated by one over-eager (or sock-puppet) account.
    'per_student_favourite_cap' => 3,

    // Cold-start floor: below this many distinct engaged students across a teacher's counted
    // lessons, show "data belum mencukupi" rather than a misleading score, and never rank them.
    'min_engaged_students' => 8,

    // Usernames matching these LIKE patterns are excluded from every engagement signal (seeded
    // test + autopilot accounts), so they never move a real teacher's score.
    'excluded_username_patterns' => ['autopilot.%'],

];
