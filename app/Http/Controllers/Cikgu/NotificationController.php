<?php

namespace App\Http\Controllers\Cikgu;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * The teacher's activity feed. Opening it marks everything read, so the bell clears.
     */
    public function index(Request $request): View
    {
        $teacher = $request->user();

        $notifications = $teacher->teacherNotifications()
            ->latest()
            ->paginate(30);

        // Read the unread ones AFTER fetching, so this render can still highlight what was new.
        $teacher->teacherNotifications()->whereNull('read_at')->update(['read_at' => now()]);

        return view('cikgu.notifikasi', ['notifications' => $notifications]);
    }

    /**
     * Mark every unread notification read. Called by the bell dropdown when it opens, so the
     * badge clears without a page load.
     */
    public function markRead(Request $request): JsonResponse
    {
        $request->user()->teacherNotifications()->whereNull('read_at')->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }
}
