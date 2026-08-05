<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The admin notification feed and its bell, mirroring the teacher one but school-scoped: an admin
 * sees the notifications of their own school, plus any unscoped ones (a user with no school).
 */
class AdminNotificationController extends Controller
{
    /**
     * The notifications visible to the signed-in admin: their school's, plus unscoped ones.
     */
    public static function scopeFor(?int $schoolId): Builder
    {
        return AdminNotification::query()
            ->where(fn (Builder $q) => $q->where('school_id', $schoolId)->orWhereNull('school_id'));
    }

    public function index(Request $request): View
    {
        $admin = $request->user();

        $notifications = self::scopeFor($admin->school_id)->latest()->paginate(30);

        // Read the unread ones AFTER fetching, so this render can still highlight what was new.
        self::scopeFor($admin->school_id)->whereNull('read_at')->update(['read_at' => now()]);

        return view('admin.notifikasi', ['notifications' => $notifications]);
    }

    public function markRead(Request $request): JsonResponse
    {
        self::scopeFor($request->user()->school_id)->whereNull('read_at')->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }
}
