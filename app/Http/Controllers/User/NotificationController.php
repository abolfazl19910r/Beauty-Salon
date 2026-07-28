<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Traits\HandlesApiResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use HandlesApiResponse;

    public function index()
    {
        $notifications = auth()->user()
            ->notifications()
            ->paginate(15);

        return view('notifications.index', compact('notifications'));
    }

    public function markAsRead(Request $request, $id)
    {
        $notification = auth()->user()
            ->notifications()
            ->findOrFail($id);

        $notification->markAsRead();

        return $this->successResponse();
    }

    public function markAllAsRead(): \Illuminate\Http\RedirectResponse
    {
        auth()->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'همه اعلان‌ها خوانده شدند.');
    }

    public function destroy($id): \Illuminate\Http\RedirectResponse
    {
        auth()->user()
            ->notifications()
            ->findOrFail($id)
            ->delete();

        return back()->with('success', 'اعلان با موفقیت حذف شد.');
    }
}
