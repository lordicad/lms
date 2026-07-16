<?php

namespace App\Services;

use App\Models\Lesson;
use App\Models\User;
use Throwable;

/**
 * Decides whether a YouTube lesson is the teacher's own work (feeds the talent signal) or a
 * reference (watchable, but excluded). The single source of truth for ownership attribution,
 * used at lesson save time and again when a teacher connects/disconnects a channel.
 */
class OwnershipService
{
    public const STATUS_OWNED = 'owned';         // channel matches a verified channel of this teacher

    public const STATUS_REFERENCE = 'reference'; // a real video from a channel the teacher does not own

    public const STATUS_BLOCKED = 'blocked';     // private/deleted video → the save must be rejected

    public const STATUS_DEFERRED = 'deferred';   // transient API failure → save as reference, re-check later

    public function __construct(private YoutubeApi $api) {}

    /**
     * Attribute a YouTube video to a teacher. Never throws: a network blip becomes a DEFERRED
     * reference rather than a lost lesson.
     *
     * @return array{status:string, ownership:?string, counts_for_talent:bool, youtube_channel_id:?string}
     */
    public function attributeYoutube(string $youtubeId, User $teacher): array
    {
        try {
            $channelId = $this->api->videoChannelId($youtubeId);
        } catch (Throwable $e) {
            report($e);

            return [
                'status' => self::STATUS_DEFERRED,
                'ownership' => Lesson::OWNERSHIP_REFERENCE,
                'counts_for_talent' => false,
                'youtube_channel_id' => null,
            ];
        }

        if ($channelId === null) {
            return [
                'status' => self::STATUS_BLOCKED,
                'ownership' => null,
                'counts_for_talent' => false,
                'youtube_channel_id' => null,
            ];
        }

        $owned = $teacher->youtubeChannels()->where('channel_id', $channelId)->exists();

        return [
            'status' => $owned ? self::STATUS_OWNED : self::STATUS_REFERENCE,
            'ownership' => $owned ? Lesson::OWNERSHIP_OWNED : Lesson::OWNERSHIP_REFERENCE,
            'counts_for_talent' => $owned,
            // Snapshot the channelId even for references, so re-attribution on a later connect is a
            // pure DB comparison (no extra API call).
            'youtube_channel_id' => $channelId,
        ];
    }

    /**
     * Re-scan a teacher's YouTube lessons against their currently-verified channels and flip
     * ownership/counts to match. Called after a connect (reference → owned) or disconnect
     * (owned → reference). Uses the stored channelId snapshot, so it makes no API calls.
     *
     * @return int number of lessons whose attribution changed
     */
    public function reattributeForTeacher(User $teacher): int
    {
        $verified = $teacher->youtubeChannels()->pluck('channel_id')->all();
        $changed = 0;

        $teacher->lessons()
            ->where('source', Lesson::SOURCE_YOUTUBE)
            ->whereNotNull('youtube_channel_id')
            ->get()
            ->each(function (Lesson $lesson) use ($verified, &$changed) {
                $owned = in_array($lesson->youtube_channel_id, $verified, true);
                $ownership = $owned ? Lesson::OWNERSHIP_OWNED : Lesson::OWNERSHIP_REFERENCE;

                if ($lesson->ownership !== $ownership || $lesson->counts_for_talent !== $owned) {
                    $lesson->update(['ownership' => $ownership, 'counts_for_talent' => $owned]);
                    $changed++;
                }
            });

        return $changed;
    }
}
