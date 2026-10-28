<?php

namespace App\Services\Booking;

use App\Models\Booking;
use App\Models\BookingPolicy;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * مشتری بدقول (بسته‌ی 5ه، تصمیم ۱۶): دست‌کم no_show_threshold بار «نیامد» در no_show_window_days روز گذشته (پیش‌فرض ۲ در ۹۰، قابل
 * تنظیم مدیر؛ آستانه‌ی خالی/صفر = خاموش). «نیامد»های پیش از برداشتن دستی محدودیت توسط مدیر (users.no_show_cleared_at) شمرده نمی‌شوند.
 * فقط نوبت‌های همین سالن (مشتری هر سالن حساب جدا دارد).
 */
class NoShowRecord
{
    public function count(User|int $user, ?BookingPolicy $policy = null): int
    {
        $user = $user instanceof User ? $user : User::find($user);
        if (! $user) {
            return 0;
        }

        $policy ??= BookingPolicy::current();
        $since = now()->subDays(max(1, (int) $policy->no_show_window_days));
        if ($user->no_show_cleared_at && Carbon::parse($user->no_show_cleared_at)->gt($since)) {
            $since = Carbon::parse($user->no_show_cleared_at);
        }

        return Booking::query()
            ->where('user_id', $user->id)
            ->where('status', 'no_show')
            ->where('no_show_at', '>', $since->format('Y-m-d H:i:s'))
            ->count();
    }

    public function isFlagged(User|int|null $user, ?BookingPolicy $policy = null): bool
    {
        if ($user === null) {
            return false;
        }

        $policy ??= BookingPolicy::current();
        $threshold = BookingPolicy::limit($policy->no_show_threshold);

        return $threshold !== null && $this->count($user, $policy) >= $threshold;
    }

    /** برچسب کادر: «بدقول: ۲ بار نیامد در ۹۰ روز» یا null */
    public function label(User|int|null $user, ?BookingPolicy $policy = null): ?string
    {
        if ($user === null) {
            return null;
        }

        $policy ??= BookingPolicy::current();
        if (! $this->isFlagged($user, $policy)) {
            return null;
        }

        return sprintf('بدقول: %s بار نیامد در %s روز', to_persian_num((string) $this->count($user, $policy)), to_persian_num((string) $policy->no_show_window_days));
    }

    /**
     * برچسب بدقولی برای یک فهرست (صفحه‌ی نوبت‌ها) با دو کوئری، نه دو کوئری برای هر ردیف.
     *
     * @param  iterable<int>  $userIds
     * @return array<int, string> فقط مشتری‌های بدقول
     */
    public function labelsFor(iterable $userIds): array
    {
        $ids = collect($userIds)->filter()->map(fn ($id) => (int) $id)->unique()->values();
        $policy = BookingPolicy::current();
        $threshold = BookingPolicy::limit($policy->no_show_threshold);
        if ($ids->isEmpty() || $threshold === null) {
            return [];
        }

        $since = now()->subDays(max(1, (int) $policy->no_show_window_days));
        $cleared = User::query()->whereIn('id', $ids)->pluck('no_show_cleared_at', 'id');
        $rows = Booking::query()->whereIn('user_id', $ids)->where('status', 'no_show')
            ->where('no_show_at', '>', $since->format('Y-m-d H:i:s'))->get(['user_id', 'no_show_at']);

        $labels = [];
        foreach ($rows->groupBy('user_id') as $userId => $noShows) {
            $from = $cleared[$userId] ?? null;
            $count = $from ? $noShows->filter(fn ($b) => Carbon::parse($b->no_show_at)->gt(Carbon::parse($from)))->count() : $noShows->count();
            if ($count >= $threshold) {
                $labels[(int) $userId] = sprintf('بدقول: %s بار نیامد در %s روز', to_persian_num((string) $count), to_persian_num((string) $policy->no_show_window_days));
            }
        }

        return $labels;
    }

    /** پیام مشتری بدقول در صفحه‌ی رزرو (وب و اپ) */
    public static function customerNotice(bool $prepaymentRequired): string
    {
        return $prepaymentRequired
            ? 'به‌خاطر نیامدن در نوبت‌های قبلی، رزرو آنلاین شما در این سالن فقط با پیش‌پرداخت ممکن است.'
            : 'به‌خاطر نیامدن در نوبت‌های قبلی، نوبت شما بعد از تأیید سالن قطعی می‌شود.';
    }

    /** برای API اپ همکار */
    public function forApi(User|int|null $user): ?array
    {
        $policy = BookingPolicy::current();
        if ($user === null || ! $this->isFlagged($user, $policy)) {
            return null;
        }

        return ['count' => $this->count($user, $policy), 'days' => (int) $policy->no_show_window_days, 'label' => $this->label($user, $policy)];
    }
}
