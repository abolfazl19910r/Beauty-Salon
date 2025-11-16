<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AdminNotificationController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $notifications = $user->notifications()->latest()->paginate(20);

        return view('admin.notifications.index', compact('notifications'));
    }

    public function markAsRead($id)
    {
        $notification = Auth::user()->notifications()->find($id);

        if ($notification && is_null($notification->read_at)) {
            $notification->markAsRead();
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false], 404);
    }

    public function markAllAsRead()
    {
        Auth::user()->unreadNotifications->markAsRead();

        return redirect()->route('admin.notifications.index')
            ->with('success', 'تمام اعلانات به عنوان خوانده‌شده علامت‌گذاری شدند.');
    }

    public function deleteAll()
    {
        $deleted = DB::table('user_notifications')
        ->where('user_id', Auth::id())
            ->delete();

        return redirect()->route('admin.notifications.index')
            ->with('success', 'تمام اعلانات با موفقیت حذف شدند.');
    }

    public function unreadCount()
    {
        $user = Auth::user();
        return response()->json([
            'count' => $user->unreadNotifications->count()
        ]);
    }

    public function latest()
    {
        $user = Auth::user();
        $notifications = $user->unreadNotifications()
            ->whereNotNull('data->message')
            ->limit(5)
            ->get()
            ->map(function ($notification) {

                $timeAgo = $notification->created_at->diffForHumans();
                if (function_exists('verta')) {
                    $timeAgo = verta($notification->created_at)->formatDifference();
                }

                $message = $notification->data['message'] ?? 'اعلان ناشناس (اطلاعات کامل نیست)';
                $link = $notification->data['link'] ?? route('admin.notifications.index');

                return [
                    'id' => $notification->id,
                    'message' => $message,
                    'time_ago' => $timeAgo,
                    'link' => $link,
                ];
            });

        return response()->json([
            'notifications' => $notifications,
        ]);
    }

}
