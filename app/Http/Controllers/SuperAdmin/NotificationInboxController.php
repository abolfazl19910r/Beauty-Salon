<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\UserNotification;
use App\Notifications\SuperAdmin\PlatformAlertNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** ⭐ «صندوق اعلان» ابرمدیر (۲۰۲۶-۱۰-۱۵) */
class NotificationInboxController extends Controller
{
    public static function query(int $userId)
    {
        return UserNotification::query()
            ->where('notifiable_type', (new \App\Models\User)->getMorphClass())
            ->where('notifiable_id', $userId)
            ->where('type', PlatformAlertNotification::class);
    }

    public function index(Request $request): View
    {
        $kind = array_key_exists((string) $request->query('kind'), PlatformAlertNotification::KINDS) ? (string) $request->query('kind') : null;
        $unreadOnly = $request->boolean('unread');

        $notifications = self::query($request->user()->id)
            ->when($kind, fn ($q) => $q->where('data', 'like', '%"kind":"'.$kind.'"%'))
            ->when($unreadOnly, fn ($q) => $q->whereNull('read_at'))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('superadmin.notifications.index', [
            'notifications' => $notifications,
            'kinds' => PlatformAlertNotification::KINDS,
            'filters' => ['kind' => $kind, 'unread' => $unreadOnly],
            'unreadCount' => self::query($request->user()->id)->whereNull('read_at')->count(),
        ]);
    }

    public function open(Request $request, string $id): RedirectResponse
    {
        $notification = self::query($request->user()->id)->findOrFail($id);
        $notification->markAsRead();
        $link = (string) ($notification->data['link'] ?? '');

        return str_starts_with($link, '/') ? redirect($link) : redirect()->route('superadmin.notifications.index');
    }

    public function readAll(Request $request): RedirectResponse
    {
        self::query($request->user()->id)->whereNull('read_at')->update(['read_at' => now()]);

        return back()->with('success', 'همه‌ی اعلان‌ها خوانده شد.');
    }
}
