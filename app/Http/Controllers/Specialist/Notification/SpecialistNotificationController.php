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

    /**
     * ⭐ نگاشت هر کلاس Notification به یک دسته‌ی قابل‌فیلتر — برای پاسخ به درخواست «مشاهده‌ی جداگانه‌ی
     * اعلانات» در صفحه‌ی اعلانات متخصص.
     */
    private const CATEGORY_MAP = [
        'App\\Notifications\\Booking\\BookingNotification' => 'booking',
        'App\\Notifications\\Booking\\BookingStatusUpdated' => 'booking',
        'App\\Notifications\\Booking\\SpecialistBookingCancelledNotification' => 'booking',
        'App\\Notifications\\Booking\\BookingRescheduledNotification' => 'booking',
        'App\\Notifications\\Withdrawal\\Approved\\WithdrawalApprovedNotification' => 'withdrawal',
        'App\\Notifications\\Withdrawal\\Rejected\\WithdrawalRejectedNotification' => 'withdrawal',
        'App\\Notifications\\Leave\\LeaveStatusNotification' => 'leave',
        'App\\Notifications\\Review\\NewReviewNotification' => 'review',
        'App\\Notifications\\Review\\NewReviewReceivedNotification' => 'review',
        'App\\Notifications\\Loyalty\\PointsEarned' => 'loyalty',
    ];

    public const CATEGORIES = [
        'booking' => 'نوبت‌ها',
        'withdrawal' => 'برداشت وجه',
        'leave' => 'مرخصی',
        'review' => 'نظرات',
        'loyalty' => 'وفاداری',
    ];

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

        // ⭐ Fix (item 9): specialist can now filter/view notifications separately by category
        // (booking/withdrawal/leave/review/loyalty), instead of only ever seeing one combined stream.
        $counts = $allNotifications
            ->groupBy(fn ($n) => self::CATEGORY_MAP[$n->type] ?? 'other')
            ->map->count();
        $counts['all'] = $allNotifications->count();

        $selectedCategory = request('category', 'all');

        if ($selectedCategory !== 'all') {
            $allNotifications = $allNotifications->filter(
                fn ($n) => (self::CATEGORY_MAP[$n->type] ?? 'other') === $selectedCategory
            )->values();
        }

        $perPage = 15;
        $currentPage = request()->get('page', 1);
        $pagedData = $allNotifications->slice(($currentPage - 1) * $perPage, $perPage)->values();

        $notifications = new LengthAwarePaginator(
            $pagedData,
            $allNotifications->count(),
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return view('specialist.notifications', compact('specialist', 'notifications', 'counts', 'selectedCategory'));
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

    /**
     * ⭐ public (not private) so the Blade view can call it directly instead of maintaining its own
     * separate, duplicated copy of this same link-resolution logic — that duplication is exactly
     * what caused the 'review_id' branch to be missing from the Blade's copy even after other code
     * paths already handled similar cases correctly.
     */
    public function resolveNotificationLink(string $type, array $data): string
    {
        if ($type === 'App\Notifications\Loyalty\PointsEarned') {
            return route('specialist.loyalty');
        }

        // ⭐ Fix (item 8): NewReviewReceivedNotification's payload has 'review_id' (not
        // 'booking_id') — this branch was missing entirely, so clicking a "new review received"
        // notification always fell through to the generic dashboard fallback below, instead of
        // landing on the actual review's page where the specialist can respond to it.
        if (! empty($data['review_id'])) {
            return route('specialist.reviews.show', $data['review_id']);
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
