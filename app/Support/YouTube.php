<?php

namespace App\Support;

class YouTube
{
    /**
     * Pull the 11-character video id out of any YouTube URL a teacher is likely to paste.
     *
     * Handles watch?v=, youtu.be/, /embed/, /shorts/, /live/, and a bare id. Extra query
     * params (t=, si=, list=) are ignored. Returns null when there is no valid id, which
     * the form request turns into a Bahasa Melayu error.
     */
    public static function parseId(?string $input): ?string
    {
        $input = trim((string) $input);

        if ($input === '') {
            return null;
        }

        // A bare id pasted on its own.
        if (self::isValidId($input)) {
            return $input;
        }

        // Tolerate a missing scheme: "youtu.be/abc" parses as a path, not a host, without this.
        if (! preg_match('#^https?://#i', $input)) {
            $input = 'https://'.ltrim($input, '/');
        }

        $parts = parse_url($input);

        if ($parts === false || ! isset($parts['host'])) {
            return null;
        }

        $host = strtolower($parts['host']);
        $host = preg_replace('/^(www|m|music)\./', '', $host);

        if (! in_array($host, ['youtube.com', 'youtube-nocookie.com', 'youtu.be'], true)) {
            return null;
        }

        $path = trim($parts['path'] ?? '', '/');

        // youtu.be/<id>
        if ($host === 'youtu.be') {
            return self::firstValidSegment($path);
        }

        // youtube.com/watch?v=<id>
        parse_str($parts['query'] ?? '', $query);

        if (isset($query['v']) && self::isValidId((string) $query['v'])) {
            return (string) $query['v'];
        }

        // youtube.com/{embed,shorts,live,v}/<id>
        $segments = explode('/', $path);

        if (count($segments) >= 2 && in_array($segments[0], ['embed', 'shorts', 'live', 'v'], true)) {
            return self::isValidId($segments[1]) ? $segments[1] : null;
        }

        return null;
    }

    public static function isValidId(string $id): bool
    {
        return (bool) preg_match('/^[A-Za-z0-9_-]{11}$/', $id);
    }

    private static function firstValidSegment(string $path): ?string
    {
        $first = explode('/', $path)[0] ?? '';

        return self::isValidId($first) ? $first : null;
    }
}
