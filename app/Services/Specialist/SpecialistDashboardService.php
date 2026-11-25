<?php

namespace App\Services\Specialist;

use App\Models\Specialist;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Repositories\Contracts\WalletTransactionRepositoryInterface;
use Carbon\Carbon;

class SpecialistDashboardService
{
    /**
     * ⭐ «برنامه‌ی امروز» و «۷ روز آینده» (تصمیم ۲۰۲۶-۱۰-۱۰، بسته‌ی ۲ اپلیکیشن — همین قاعده برای API همکار):
     * همه‌ی نوبت‌های فعال، چه پرداخت‌شده چه نه. قبلاً فقط payment_status=paid می‌آمد: نوبت دستی مدیر که پولش حضوری
     * گرفته می‌شود (unpaid) پنهان بود و نوبت لغوشده‌ی پرداخت‌شده دیده می‌شد. لغوشده و pending_payment (مشتری هنوز
     * در درگاه؛ اگر نپردازد خودکار لغو می‌شود) نمایش داده نمی‌شوند. درآمدها همچنان فقط پرداخت‌شده‌ی لغونشده‌اند.
     */
    public const AGENDA_STATUSES = ['pending', 'confirmed', 'completed'];

    /** «برنامه‌ی امروز»: نوبت‌های فعال + «نیامد» (۲۰۲۶-۱۰-۲۲: پیش‌تر با زدن «نیامد» از فهرست امروز ناپدید می‌شد) */
    public const TODAY_STATUSES = ['pending', 'confirmed', 'completed', 'no_show'];

    public function __construct(
        private readonly BookingRepositoryInterface $bookingRepository,
        private readonly WalletTransactionRepositoryInterface $walletTransactions,
    ) {}

    /**
     * داشبورد وب کارشناس (بسته‌ی ۳ ظاهری، ۲۰۲۶-۱۱-۲۵؛ تصمیم‌های «الف»): «نوبت بعدی» از همین لحظه با کار مستقیم، سه عدد
     * (درآمد ماه شمسی = درآمد خالص کیف پول، قابل برداشت، امتیاز یا null)، بقیه‌ی امروز، «۷ روز آینده» از فردا، و میله‌های
     * درآمد خالص ۷ روز گذشته از همان منبع کارت درآمد. «آمار کل» در صفحه‌ی گزارش است.
     * کلیدهای getDashboardData (برنامه‌ی امروز، لغوشده‌های امروز، شمار امروز) هم برمی‌گردند؛ API همکار جدا از همان متدها می‌خواند.
     */
    public function getOverviewData(Specialist $specialist): array
    {
        $now = Carbon::now();
        $todaySchedule = $this->getTodaySchedule($specialist);
        $nextBooking = $this->getNextBooking($specialist, $now);

        [$monthStart, $monthEnd, $monthLabel] = SpecialistWalletService::jalaliMonth($now);
        $wallet = $specialist->wallet;

        $weekStart = Carbon::today()->subDays(6);
        $byDay = $wallet ? $this->walletTransactions->netIncomeByDayForWallet($wallet->id, $weekStart, Carbon::tomorrow()) : [];
        $weeklyIncome = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $weeklyIncome[] = [
                'day' => \App\Support\Fa::digits(verta($date)->format('j')),
                'label' => \App\Support\Fa::date($date, weekday: false),
                'total' => (float) ($byDay[$date->format('Y-m-d')] ?? 0),
                'today' => $i === 0,
            ];
        }

        return [
            'todayPersian' => \App\Support\Fa::date($now),
            'todaySchedule' => $todaySchedule,
            'todayCancelled' => $this->getTodayCancelled($specialist),
            'todayBookingsCount' => $this->getTodayBookingsCount($specialist),
            'todayOthers' => $todaySchedule->reject(fn ($b) => $nextBooking && $b->id === $nextBooking->id)->values(),
            'nextBooking' => $nextBooking,
            'nextAction' => $nextBooking ? self::primaryAction($nextBooking, $now) : null,
            'monthLabel' => $monthLabel,
            'monthIncome' => $wallet ? $this->walletTransactions->sumNetIncomeForWalletBetween($wallet->id, $monthStart, $monthEnd) : 0.0,
            'withdrawable' => (float) ($wallet?->balance ?? 0),
            'averageRating' => $this->getRatingOrNull($specialist),
            'upcomingDays' => $this->getUpcomingFromTomorrow($specialist)->reject(fn ($b) => $nextBooking && $b->id === $nextBooking->id)->values(),
            'weeklyIncome' => $weeklyIncome,
        ];
    }

    /**
     * کار اصلی یک نوبت در پنل کارشناس — همان نام و همان رنگ همه‌جا (داشبورد، فهرست نوبت‌ها):
     * منتظر تأیید ← «تأیید»؛ تأییدشده‌ای که ساعتش رسیده ← «انجام شد»؛ بقیه ← «جزئیات» (تصمیم ۱۱-۲۵: «الف»).
     *
     * @return array{kind: string, label: string, route: string|null}
     */
    public static function primaryAction(\App\Models\Booking $booking, ?Carbon $now = null): array
    {
        $now ??= Carbon::now();

        return match (true) {
            $booking->status === 'pending' => ['kind' => 'confirm', 'label' => 'تأیید', 'route' => route('specialist.bookings.complete', $booking)],
            $booking->status === 'confirmed' && $booking->booking_time->lte($now) => ['kind' => 'complete', 'label' => 'انجام شد', 'route' => route('specialist.bookings.mark-completed', $booking)],
            default => ['kind' => 'details', 'label' => 'جزئیات', 'route' => null],
        };
    }

    /** نزدیک‌ترین نوبت فعال که هنوز تمام نشده (شروع + مدت خدمت بعد از اکنون) */
    public function getNextBooking(Specialist $specialist, ?Carbon $now = null): ?\App\Models\Booking
    {
        $now ??= Carbon::now();

        return $this->bookingRepository->query()->where('specialist_id', $specialist->id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->where('booking_time', '>=', $now->copy()->subHours(6))
            ->with(['service', 'user'])
            ->orderBy('booking_time')
            ->limit(20)
            ->get()
            ->first(fn ($b) => $b->booking_time->copy()->addMinutes((int) ($b->service?->duration ?: 30))->gt($now));
    }

    private function getRatingOrNull(Specialist $specialist): ?float
    {
        $avg = $this->bookingRepository->query()->where('specialist_id', $specialist->id)
            ->whereNotNull('rating')
            ->avg('rating');

        return $avg === null ? null : round((float) $avg, 1);
    }

    private function getUpcomingFromTomorrow(Specialist $specialist)
    {
        return $this->bookingRepository->query()->where('specialist_id', $specialist->id)
            ->where('booking_time', '>=', Carbon::tomorrow())
            ->where('booking_time', '<', Carbon::today()->addDays(8))
            ->whereIn('status', self::AGENDA_STATUSES)
            ->with(['service', 'user'])
            ->orderBy('booking_time')
            ->limit(6)
            ->get();
    }

    public function getTodaySchedule(Specialist $specialist)
    {
        return $this->bookingRepository->query()->where('specialist_id', $specialist->id)
            ->whereDate('booking_time', Carbon::today())
            ->whereIn('status', self::TODAY_STATUSES)
            ->with(['service', 'user'])
            ->orderBy('booking_time', 'asc')
            ->get();
    }

    /** لغوشده‌های امروز (بخش جمع‌شده‌ی داشبورد و «امروز» اپ همکار)، با لغوکننده و دلیل */
    public function getTodayCancelled(Specialist $specialist)
    {
        return $this->bookingRepository->query()->where('specialist_id', $specialist->id)
            ->whereDate('booking_time', Carbon::today())
            ->where('status', 'cancelled')
            ->with(['service', 'user'])
            ->orderBy('booking_time', 'asc')
            ->get();
    }

    private function getTodayBookingsCount(Specialist $specialist): int
    {
        return $this->bookingRepository->query()->where('specialist_id', $specialist->id)
            ->whereDate('booking_time', Carbon::today())
            ->whereIn('status', self::AGENDA_STATUSES)
            ->count();
    }

    public function getTodayRevenue(Specialist $specialist): float
    {
        return $this->bookingRepository->query()->where('specialist_id', $specialist->id)
            ->whereDate('booking_time', Carbon::today())
            ->where('payment_status', 'paid')
            ->where('status', '!=', 'cancelled')
            ->sum('prepayment_amount');
    }
}
