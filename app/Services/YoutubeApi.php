<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Thin wrapper over the two YouTube Data API v3 endpoints we use. No google/apiclient - just
 * Laravel's HTTP client, so it is trivially fake-able in tests (Http::fake).
 *
 *   ownedChannels()   - channels.list?mine=true, with the teacher's OAuth access token.
 *   videoChannelId()  - videos.list by API key (public + unlisted), to find who owns a video.
 *
 * videoChannelId() is cached per video id for 24h to avoid re-charging quota on lesson edits.
 */
class YoutubeApi
{
    private const BASE = 'https://www.googleapis.com/youtube/v3';

    /**
     * Channels owned by the Google account behind $accessToken. Empty array = the account has
     * no YouTube channel. Throws on API/network error (the caller surfaces a friendly message).
     *
     * @return array<int, array{channel_id:string, title:string, thumbnail_url:?string}>
     */
    public function ownedChannels(string $accessToken): array
    {
        $resp = Http::withToken($accessToken)
            ->timeout(12)
            ->get(self::BASE.'/channels', [
                'part' => 'snippet',
                'mine' => 'true',
                'maxResults' => 50,
            ]);

        $resp->throw();

        return collect($resp->json('items', []))
            ->map(fn (array $item) => [
                'channel_id' => (string) $item['id'],
                'title' => (string) data_get($item, 'snippet.title', $item['id']),
                'thumbnail_url' => data_get($item, 'snippet.thumbnails.default.url'),
            ])
            ->all();
    }

    /**
     * The owning channelId and length of a public/unlisted video, in one call. Returns null when
     * the video is private or deleted (no item). Throws on a transient API/network error - the
     * caller degrades gracefully rather than losing the lesson. Cached 24h per video id (misses are
     * not cached, so a video flipped from private to public is picked up on the next save).
     *
     * @return array{channel_id:string, duration_seconds:?int}|null
     */
    public function videoInfo(string $videoId): ?array
    {
        return Cache::remember("yt:video-info:{$videoId}", now()->addDay(), function () use ($videoId) {
            $resp = Http::timeout(12)->get(self::BASE.'/videos', [
                'part' => 'snippet,contentDetails',
                'id' => $videoId,
                'key' => (string) config('services.youtube.key'),
            ]);

            $resp->throw();

            $items = $resp->json('items', []);

            if (empty($items)) {
                return null;
            }

            return [
                'channel_id' => (string) data_get($items[0], 'snippet.channelId'),
                'duration_seconds' => self::parseDuration(data_get($items[0], 'contentDetails.duration')),
            ];
        });
    }

    /**
     * The channelId that owns a public/unlisted video, or null when private/deleted.
     */
    public function videoChannelId(string $videoId): ?string
    {
        return $this->videoInfo($videoId)['channel_id'] ?? null;
    }

    /**
     * ISO 8601 duration ("PT15M20S") → whole seconds, or null when absent/unparseable.
     */
    public static function parseDuration(?string $iso): ?int
    {
        if (! $iso || ! str_starts_with($iso, 'P')) {
            return null;
        }

        try {
            $i = new \DateInterval($iso);
        } catch (\Throwable) {
            return null;
        }

        return $i->d * 86400 + $i->h * 3600 + $i->i * 60 + $i->s;
    }
}
