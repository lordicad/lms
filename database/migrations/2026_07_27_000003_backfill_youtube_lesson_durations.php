<?php

use App\Models\Lesson;
use App\Services\YoutubeApi;
use Illuminate\Database\Migrations\Migration;

/**
 * Fill in duration_seconds for YouTube lessons that never had one.
 *
 * Uploaded videos capture their length from the player on first play, but a YouTube video that
 * nobody has watched yet had none — so its card showed no duration badge. This fetches the length
 * from the YouTube Data API once, per video. Best effort: no API key, or a video that can't be
 * fetched, simply leaves the duration null (exactly as before) rather than failing the deploy.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! config('services.youtube.key')) {
            return;
        }

        $api = app(YoutubeApi::class);

        Lesson::query()
            ->where('source', Lesson::SOURCE_YOUTUBE)
            ->whereNotNull('youtube_id')
            ->whereNull('duration_seconds')
            ->get(['id', 'youtube_id'])
            ->each(function (Lesson $lesson) use ($api) {
                try {
                    $seconds = $api->videoInfo($lesson->youtube_id)['duration_seconds'] ?? null;

                    if ($seconds) {
                        $lesson->forceFill(['duration_seconds' => $seconds])->save();
                    }
                } catch (\Throwable $e) {
                    // A video that can't be fetched keeps its null duration.
                }
            });
    }

    public function down(): void
    {
        // Durations are recaptured on play; nothing to reverse.
    }
};
