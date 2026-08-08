<?php

namespace App\Http\Controllers\Specialist\Notification;

use App\Http\Controllers\Controller;
use App\Models\Specialist;
use App\Traits\HandlesApiResponse;
use App\Traits\HasJalaliDates;
use App\Traits\ResolvesSpecialist;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class SpecialistNotificationController extends Controller
{
    use HandlesApiResponse;
    use HasJalaliDates;
    use ResolvesSpecialist;

    public function index(): View
    {
        $user = auth()->user();
        $specialist = Specialist::where('phone', $user->phone)->first();

        if (! $specialist) {
            return view('specialist.profile-not-found');
        }

        $userNotifications = $user->notifications()
            ->orderBy('created_at', 'desc')
            ->get();

        $specialistNotifications = $specialist->notifications()
            ->orderBy('created_at', 'desc')
            ->get();

        $allNotifications = $userNotifications->merge($specialistNotifications)
            ->sortByDesc('created_at')
            ->values();

        $perPage = 15;
        $currentPage = request()->get('page', 1);
        $pagedData = $allNotifications->slice(($currentPage - 1) * $perPage, $perPage)->values();

        $notifications = new LengthAwarePaginator(
            $pagedData,
            $allNotifications->count(),
            $perPage,
            $currentPage,
            ['path' => request()->url()]
        );

        return view('specialist.notifications', compact('specialist', 'notifications'));
    }

    public function latest(): JsonResponse
    {
        $user = auth()->user();
        $specialist = Specialist::where('phone', $user->phone)->first();

        $userNotifications = $user->notifications()
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $specialistNotifications = collect([]);
        if ($specialist) {
            $specialistNotifications = $specialist->notifications()
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();
        }

        $allNotifications = $userNotifications->merge($specialistNotifications)
            ->sortByDesc('created_at')
            ->take(5)
            ->values();

        $notifications = $allNotifications->map(function ($notification) {
            $data = $notification->data;
            $text = $data['message'] ?? $data['description'] ?? 'اعلان جدید';
            $link = $this->resolveNotificationLink($notification->type, $data);

            return [
                'id' => $notification->id,
                'message' => $text,
                'link' => $link,
                'read_at' => $notification->read_at,
                'time_ago' => $this->timeAgo($notification->created_at),
            ];
        });

        return response()->json(['notifications' => $notifications]);
    }

    public function count(): JsonResponse
    {
        $user = auth()->user();
        $specialist = Specialist::where('phone', $user->phone)->first();

        $userUnread = $user->unreadNotifications()->count();
        $specialistUnread = $specialist ? $specialist->unreadNotifications()->count() : 0;

        return response()->json([
            'count' => $userUnread + $specialistUnread,
        ]);
    }

    public function markAsRead(string $id): JsonResponse
    {
        $user = auth()->user();
        $specialist = Specialist::where('phone', $user->phone)->first();

        $notification = $user->notifications()->find($id);

        if (! $notification && $specialist) {
            $notification = $specialist->notifications()->find($id);
        }

        if ($notification && ! $notification->read_at) {
            $notification->markAsRead();
        }

        return $this->successResponse();
    }

    public function showAndRedirect(string $id): RedirectResponse
    {
        $user = auth()->user();
        $specialist = Specialist::where('phone', $user->phone)->first();

        $notification = $user->notifications()->find($id);

        if (! $notification && $specialist) {
            $notification = $specialist->notifications()->find($id);
        }

        if ($notification) {
            if (! $notification->read_at) {
                $notification->markAsRead();
            }

            $data = $notification->data;
            $link = $this->resolveNotificationLink($notification->type, $data);

            if (empty($link) || $link === '#') {
                return redirect()->route('specialist.my-dashboard')
                    ->with('warning', 'لینک اعلان نامعتبر است.');
            }

            return redirect()->to($link);
        }

        return redirect()->route('specialist.my-dashboard')
            ->with('error', 'اعلان مورد نظر یافت نشد.');
    }

    public function markAllAsRead(): RedirectResponse
    {
        $user = auth()->user();
        $specialist = Specialist::where('phone', $user->phone)->first();

        $user->unreadNotifications()->update(['read_at' => now()]);

        if ($specialist) {
            $specialist->unreadNotifications()->update(['read_at' => now()]);
        }

        return back()->with('success', 'تمام اعلانات به عنوان خوانده شده علامت‌گذاری شدند.');
    }

    private function resolveNotificationLink(string $type, array $data): string
    {
        if ($type === 'App\Notifications\Loyalty\PointsEarned') {
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
            $diff < 60 => 'لحظاتی پیش',
            $diff < 3600 => floor($diff / 60).' دقیقه پیش',
            $diff < 86400 => floor($diff / 3600).' ساعت پیش',
            $diff < 604800 => floor($diff / 86400).' روز پیش',
            default => $this->toJalali($datetime),
        };
    }
}
