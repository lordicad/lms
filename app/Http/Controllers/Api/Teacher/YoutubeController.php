<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Controllers\YoutubeConnectController;
use App\Models\YoutubeChannel;
use App\Services\OwnershipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * YouTube channel ownership for the mobile app.
 *
 * Connecting deliberately has NO mobile-side OAuth: the app opens `connect_url` (the existing
 * web redirect) in a browser, so consent, reading the channel list, storing the channel and
 * re-attributing the teacher's videos all run through the same web YoutubeConnectController.
 * Mobile and web therefore can never drift. Only listing and disconnecting live here, and the
 * disconnect mirrors the web one — delete, then re-attribute so lessons flip back to references.
 */
class YoutubeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $teacher = $request->user();
        if (! $teacher || ! $teacher->isTeacher()) {
            return response()->json(['message' => 'Hanya guru boleh mengakses ini.'], 403);
        }

        $channels = $teacher->youtubeChannels()->latest('verified_at')->get();

        return response()->json([
            'configured' => YoutubeConnectController::isConfigured(),
            'connect_url' => route('oauth.youtube.redirect'),
            'channels' => $channels->map(fn ($c) => [
                'id' => $c->id,
                'channel_id' => $c->channel_id,
                'title' => $c->title,
                'thumbnail_url' => $c->thumbnail_url,
                'verified_at' => $c->verified_at ? (string) $c->verified_at : null,
            ])->all(),
        ]);
    }

    public function destroy(
        Request $request,
        YoutubeChannel $channel,
        OwnershipService $ownership,
    ): JsonResponse {
        $teacher = $request->user();

        if (! $teacher || ! $teacher->isTeacher() || $channel->teacher_id !== $teacher->id) {
            return response()->json(['message' => 'Tiada kebenaran.'], 403);
        }

        $title = $channel->title;

        $channel->delete();
        $ownership->reattributeForTeacher($teacher);

        return response()->json(['disconnected' => true, 'title' => $title]);
    }
}
