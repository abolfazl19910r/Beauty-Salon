<?php

namespace App\Http\Controllers\Admin\Notification;

use App\Http\Controllers\Controller;
use App\Traits\HandlesApiResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;

class AdminNotificationController extends Controller
{
    use HandlesApiResponse;

    public function index(): View
    {
        $user = Auth::user();

        $notifications = $user->notifications()->latest()->paginate(20);

        return view('admin.notifications.index', compact('notifications'));
    }

    public function show($id): View
    {
        $user = Auth::user();
        $notification = $user->notifications()->findOrFail($id);

        if (is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        return view('admin.notifications.show', compact('notification'));
    }

    public function markAsRead($id): JsonResponse
    {
        $notification = Auth::user()->notifications()->find($id);

        if ($notification && is_null($notification->read_at)) {
            $notification->markAsRead();
            return $this->successResponse();
        }

        return $this->successResponse('اعلان قبلاً خوانده شده بود یا یافت نشد.');
    }

    public function delete($id): JsonResponse
    {
        $deleted = Auth::user()->notifications()->where('id', $id)->delete();

        if ($deleted) {
            return $this->successResponse('اعلان با موفقیت حذف شد.');
        }

        return $this->errorResponse('اعلان پیدا نشد یا حذف با شکست مواجه شد.', 404);
    }

    public function toggleRead($id): JsonResponse
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
            return $this->successResponse('وضعیت اعلان با موفقیت به‌روز شد.', ['status' => $status]);
        }

        return $this->errorResponse('اعلان پیدا نشد.', 404);
    }

    public function markAllAsRead(): RedirectResponse
    {
        Auth::user()->unreadNotifications->markAsRead();

        return redirect()->route('admin.notifications.index')
            ->with('success', 'تمام اعلانات به عنوان خوانده‌شده علامت‌گذاری شدند.');
    }

    public function deleteAll(): RedirectResponse
    {
        $deleted = DB::table('user_notifications')
            ->where('user_id', Auth::id())
            ->delete();

        return redirect()->route('admin.notifications.index')
            ->with('success', 'تمام اعلانات با موفقیت حذف شدند.');
    }

    public function unreadCount(): JsonResponse
    {
        $user = Auth::user();
        return response()->json([
            'count' => $user->unreadNotifications->count()
        ]);
    }

    public function latest(): JsonResponse
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
