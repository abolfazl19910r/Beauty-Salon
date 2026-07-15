<?php

namespace App\Http\Controllers\Specialist\Notification;

use App\Http\Controllers\Controller;
use App\Traits\ResolvesSpecialist;
use App\Models\Specialist;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Morilog\Jalali\Jalalian;

class SpecialistNotificationController extends Controller
{
    use ResolvesSpecialist;

    public function index()
    {
        $user = auth()->user();
        $specialist = Specialist::where('phone', $user->phone)->first();

        if (! $specialist) {
            return view('specialist.profile-not-found');
        }

        $notifications = $user->notifications()
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('specialist.notifications', compact('specialist', 'notifications'));
    }

    public function latest(): JsonResponse
    {
        $user = auth()->user();

        $notifications = $user->notifications()
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($notification) {
                $data = $notification->data;
                $text = $data['message'] ?? $data['description'] ?? 'اعلان جدید';
                $link = $this->resolveNotificationLink($notification->type, $data);

                return [
                    'id'       => $notification->id,
                    'message'  => $text,
                    'link'     => $link,
                    'read_at'  => $notification->read_at,
                    'time_ago' => $this->timeAgo($notification->created_at),
                ];
            });

        return response()->json(['notifications' => $notifications]);
    }

    public function count(): JsonResponse
    {
        return response()->json([
            'count' => auth()->user()->unreadNotifications()->count(),
        ]);
    }

    public function markAsRead(string $id): JsonResponse
    {
        $notification = auth()->user()->notifications()->find($id);

        if ($notification && ! $notification->read_at) {
            $notification->markAsRead();
        }

        return response()->json(['success' => true]);
    }

    public function markAllAsRead(): RedirectResponse
    {
        auth()->user()->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('success', 'تمام اعلانات به عنوان خوانده شده علامت‌گذاری شدند.');
    }

    private function resolveNotificationLink(string $type, array $data): string
    {
        if ($type === 'App\Notifications\PointsEarned') {
            return route('specialist.loyalty');
        }

        if (! empty($data['booking_id'])) {
            return route('specialist.bookings.show', $data['booking_id']);
        }

        if (! empty($data['leave_id'])) {
            return route('specialist.leaves');
        }

        return route('specialist.my-dashboard');
    }

    private function timeAgo(Carbon $datetime): string
    {
        $diff = $datetime->diffInSeconds(Carbon::now());

        return match (true) {
            $diff < 60      => 'لحظاتی پیش',
            $diff < 3600    => floor($diff / 60) . ' دقیقه پیش',
            $diff < 86400   => floor($diff / 3600) . ' ساعت پیش',
            $diff < 604800  => floor($diff / 86400) . ' روز پیش',
            default         => Jalalian::fromCarbon($datetime)->format('Y/m/d'),
        };
    }
}
