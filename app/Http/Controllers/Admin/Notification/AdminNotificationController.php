<?php

namespace App\Http\Controllers\Admin\Notification;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminNotificationController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $notifications = $user->notifications()->latest()->paginate(20);

        return view('admin.notifications.index', compact('notifications'));
    }

    public function show($id)
    {
        $user = Auth::user();
        $notification = $user->notifications()->findOrFail($id);

        if (is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        return view('admin.notifications.show', compact('notification'));
    }

    public function markAsRead($id)
    {
        $notification = Auth::user()->notifications()->find($id);

        if ($notification && is_null($notification->read_at)) {
            $notification->markAsRead();
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => true, 'message' => 'اعلان قبلاً خوانده شده بود یا یافت نشد.'], 200);
    }

    public function delete($id)
    {
        $deleted = Auth::user()->notifications()->where('id', $id)->delete();

        if ($deleted) {
            return response()->json([
                'success' => true,
                'message' => 'اعلان با موفقیت حذف شد.'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'اعلان پیدا نشد یا حذف با شکست مواجه شد.'
        ], 404);
    }

    public function toggleRead($id)
    {
        $notification = Auth::user()->notifications()->find($id);

        if ($notification) {
            if ($notification->read_at) {
                $notification->read_at = null;
                $notification->save();
                $status = 'unread';
            } else {
                $notification->markAsRead();
                $status = 'read';
            }
            return response()->json([
                'success' => true,
                'status' => $status,
                'message' => 'وضعیت اعلان با موفقیت به‌روز شد.'
            ]);
        }

        return response()->json(['success' => false, 'message' => 'اعلان پیدا نشد.'], 404);
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
