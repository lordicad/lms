<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Teacher activity feed: quiz attempts, favourites and material downloads. */
class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $teacher = $request->user();

        if (! $teacher || ! $teacher->isTeacher()) {
            return response()->json(['message' => 'Hanya guru boleh mengakses ini.'], 403);
        }

        $notifications = $teacher->teacherNotifications()
            ->latest()
            ->limit(50)
            ->get();

        return response()->json([
            'unread_count' => $teacher->teacherNotifications()->whereNull('read_at')->count(),
            'notifications' => $notifications->map(fn ($notification) => [
                'id' => $notification->id,
                'type' => $notification->type,
                'actor_name' => $notification->actor_name,
                'title' => $notification->title,
                'read' => $notification->read_at !== null,
                'created_at' => $notification->created_at?->toIso8601String(),
            ])->all(),
        ]);
    }

    public function markRead(Request $request): JsonResponse
    {
        $teacher = $request->user();

        if (! $teacher || ! $teacher->isTeacher()) {
            return response()->json(['message' => 'Hanya guru boleh mengakses ini.'], 403);
        }

        $teacher->teacherNotifications()->whereNull('read_at')->update(['read_at' => now()]);

        return response()->json(['unread_count' => 0]);
    }
}
