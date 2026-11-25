<?php

namespace App\Support\Auth;

use App\Models\Salon;
use App\Models\User;
use App\Support\CentralUrl;
use Illuminate\Support\Facades\Hash;

/**
 * راهنمای «در اشتباه» صفحه‌ی ورود (آزمون ویندوز بسته‌ی ۴، ۲۰۲۶-۱۱-۲۵؛ تصمیم ابوالفضل): حساب مشتری مال هر سالن جداست و
 * از `/s/{slug}/login` وارد می‌شود، حساب مدیر و کارشناس سراسری است و از `/login`. کسی که از صفحه‌ی دیگر می‌آمد فقط
 * «اطلاعات وارد شده صحیح نمی‌باشد» می‌دید.
 *
 * راهنما فقط وقتی برمی‌گردد که **رمز** با حساب نوع دیگر درست باشد — با شماره‌ی تنها چیزی لو نمی‌رود (نمی‌شود با
 * آزمودن شماره‌ها فهمید کدام شماره چه حسابی دارد). ورود هم انجام نمی‌شود؛ فقط لینک صفحه‌ی درست.
 *
 * @phpstan-type Hint array{text: string, url: string, label: string}
 */
final class LoginDoorHint
{
    /** @return array{text: string, url: string, label: string}|null برای صفحه‌ی ورود مشتری سالن */
    public static function forCustomerPage(string $phone, string $password): ?array
    {
        $staff = User::query()->where('user_type', 'staff')->where('phone', $phone)->first();
        if (! $staff || ! $staff->password || ! Hash::check($password, $staff->password)) {
            return null;
        }

        return [
            'text' => 'این شماره و رمز مال حساب مدیر یا کارشناس سالن است. حساب کارکنان از صفحه‌ی «ورود کارکنان» وارد می‌شود.',
            'url' => CentralUrl::route('login'),
            'label' => 'رفتن به ورود کارکنان',
        ];
    }

    /** @return array{text: string, url: string, label: string}|null برای صفحه‌ی ورود کارکنان (`/login`) */
    public static function forStaffPage(string $phone, string $password): ?array
    {
        $salonIds = User::query()->where('user_type', 'customer')->where('phone', $phone)->whereNotNull('salon_id')
            ->get(['salon_id', 'password'])
            ->filter(fn (User $u) => $u->password && Hash::check($password, $u->password))
            ->pluck('salon_id')->unique()->values();
        if ($salonIds->isEmpty()) {
            return null;
        }

        $salon = $salonIds->count() === 1 ? Salon::query()->find($salonIds->first()) : null;
        if ($salon) {
            return [
                'text' => 'این شماره و رمز مال حساب مشتری «'.$salon->name.'» است. مشتری‌ها از صفحه‌ی ورود سالن خودشان وارد می‌شوند.',
                'url' => route('salon.login', ['salon_slug' => $salon->slug]),
                'label' => 'رفتن به ورود '.$salon->name,
            ];
        }

        return [
            'text' => 'این شماره و رمز مال حساب مشتری است. مشتری‌ها از صفحه‌ی ورود سالن خودشان وارد می‌شوند (نشانی سالن، بعد «ورود»).',
            'url' => CentralUrl::route('central.home'),
            'label' => 'صفحه‌ی اصلی ماهرو',
        ];
    }
}
