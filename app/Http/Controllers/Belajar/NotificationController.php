<?php

namespace App\Http\Controllers\Belajar;

use App\Http\Controllers\Controller;
use App\Models\ContentNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The student's "new content" feed: videos, materials and quizzes their school's teachers have
 * published for their Tahun. The feed is shared (ContentNotification::scopeFor), so read state is a
 * per-student marker on the user — opening this page (or the bell) stamps it to now and clears the
 * badge.
 */
class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $notifications = ContentNotification::scopeFor($user->school_id, $user->grade_id)
            ->latest()
            ->paginate(30);

        // Mark which items are new relative to the last visit BEFORE stamping the marker, so this
        // render can still highlight them. read_at is transient here (the feed row has no read_at).
        $readAt = $user->content_notifications_read_at;
        $notifications->getCollection()->each(function (ContentNotification $n) use ($readAt) {
            $n->read_at = ($readAt && $n->created_at->lte($readAt)) ? $n->created_at : null;
        });

        $user->forceFill(['content_notifications_read_at' => now()])->save();

        return view('belajar.notifikasi', ['notifications' => $notifications]);
    }

    public function markRead(Request $request): JsonResponse
    {
        $request->user()->forceFill(['content_notifications_read_at' => now()])->save();

        return response()->json(['ok' => true]);
    }
}
