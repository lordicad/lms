<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;

/**
 * Signed, expiring links to the private uploads (see MediaController).
 *
 * The signature covers only the path and query (`signed:relative` on the routes), then the link is
 * made absolute. So it stays valid whatever scheme or host the request is seen under - http
 * behind a proxy, or the Flutter app calling the API - while still being a full URL the mobile
 * app can play.
 */
class MediaUrl
{
    public static function for(string $route, Model $model): string
    {
        $expires = now()->addMinutes(config('lms.media_url_ttl_minutes'));

        return url(URL::temporarySignedRoute($route, $expires, $model, absolute: false));
    }
}
