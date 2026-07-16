<?php

namespace App\Http\Controllers;

use App\Models\YoutubeChannel;
use App\Services\OwnershipService;
use App\Services\YoutubeApi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

/**
 * The Google OAuth "prove you own this channel" dance. We read the teacher's channel list once
 * (channels.list?mine=true) and store only the public channel identity — the access token is used
 * in-memory and discarded. No refresh token is ever stored (PDPA-friendly; re-verify = reconnect).
 */
class YoutubeConnectController extends Controller
{
    private const SCOPE = 'https://www.googleapis.com/auth/youtube.readonly';

    public function __construct(
        private YoutubeApi $api,
        private OwnershipService $ownership,
    ) {}

    /** Send the teacher to Google for consent (read-only YouTube scope). */
    public function redirect(): RedirectResponse
    {
        // The feature depends on owner-provided Google OAuth secrets. Until they are set, fail
        // gracefully instead of throwing a Socialite configuration error.
        if (! config('services.google.client_id')) {
            return redirect()->route('cikgu.bakat')
                ->with('error', __('Sambungan YouTube belum tersedia. Sila hubungi pentadbir MOE.'));
        }

        return Socialite::driver('google')
            ->scopes([self::SCOPE])
            ->with(['access_type' => 'online', 'prompt' => 'consent'])
            ->redirect();
    }

    public static function isConfigured(): bool
    {
        return (bool) config('services.google.client_id');
    }

    /** Google returns here. Exchange the code, read owned channels, store them, discard the token. */
    public function callback(Request $request): RedirectResponse
    {
        $teacher = $request->user();

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable $e) {
            report($e);

            return redirect()->route('cikgu.bakat')
                ->with('error', __('Sambungan YouTube tidak berjaya. Sila cuba lagi.'));
        }

        try {
            $channels = $this->api->ownedChannels($googleUser->token);
        } catch (Throwable $e) {
            report($e);

            return redirect()->route('cikgu.bakat')
                ->with('error', __('Tidak dapat membaca channel YouTube anda buat masa ini. Sila cuba sebentar lagi.'));
        }

        if ($channels === []) {
            return redirect()->route('cikgu.bakat')
                ->with('error', __('Akaun Google ini tiada channel YouTube.'));
        }

        // Refuse the whole batch if any channel is already claimed by a DIFFERENT teacher, before
        // writing anything — so two teachers can never both claim the same channel.
        foreach ($channels as $channel) {
            $existing = YoutubeChannel::where('channel_id', $channel['channel_id'])->first();

            if ($existing && $existing->teacher_id !== $teacher->id) {
                return redirect()->route('cikgu.bakat')
                    ->with('error', __('Channel ":title" telah dituntut oleh guru lain. Sila hubungi pentadbir jika ini satu kesilapan.', ['title' => $channel['title']]));
            }
        }

        $names = [];

        foreach ($channels as $channel) {
            YoutubeChannel::updateOrCreate(
                ['channel_id' => $channel['channel_id']],
                [
                    'teacher_id' => $teacher->id,
                    'title' => $channel['title'],
                    'thumbnail_url' => $channel['thumbnail_url'],
                    'verified_at' => now(),
                ],
            );
            $names[] = $channel['title'];
        }

        // A teacher who added their own videos before connecting now gets correctly credited.
        $flipped = $this->ownership->reattributeForTeacher($teacher);

        $message = __('YouTube disambungkan: :names.', ['names' => implode(', ', $names)]);

        if ($flipped > 0) {
            $message .= ' '.__(':count video kini dikira sebagai kandungan anda.', ['count' => $flipped]);
        }

        return redirect()->route('cikgu.bakat')->with('status', $message);
    }

    /** Disconnect a channel the teacher owns; its lessons flip back to references. */
    public function disconnect(Request $request, YoutubeChannel $channel): RedirectResponse
    {
        $teacher = $request->user();

        abort_unless($channel->teacher_id === $teacher->id, 403);

        $channel->delete();
        $this->ownership->reattributeForTeacher($teacher);

        return redirect()->route('cikgu.bakat')
            ->with('status', __('Channel ":title" telah diputuskan sambungan.', ['title' => $channel->title]));
    }
}
