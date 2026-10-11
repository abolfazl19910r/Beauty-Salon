<?php

namespace App\Services\Customer;

use App\Models\Booking;
use App\Models\PaymentTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * حذف حساب مشتری — وب و اپ (تصمیم ۲۰۲۶-۱۰-۱۱، گزینه‌ی «الف»).
 *
 * قبلاً user->delete() برای هر مشتری‌ای که حتی یک نوبت داشت با کلید خارجی bookings.user_id شکست می‌خورد (۵۰۰).
 * حالا:
 *  - نوبت فعالِ آینده یا موجودی کیف پول → رد با پیام (پول و نوبت مشتری بی‌صاحب نمی‌ماند)؛
 *  - بدون هیچ سابقه‌ی نوبت/پرداخت → حذف کامل ردیف؛
 *  - با سابقه → ناشناس‌سازی: نام/شماره/رمز/کدها پاک، توکن‌ها و اتصال ربات و اعلان‌ها حذف؛ نوبت‌ها برای
 *    حساب‌وکتاب سالن می‌مانند. همان شماره بعداً می‌تواند دوباره ثبت‌نام کند.
 */
class CustomerAccountDeletionService
{
    public const ACTIVE_STATUSES = ['pending_payment', 'pending', 'confirmed'];

    /** دلیل رد حذف، یا null اگر حذف مجاز است. */
    public function blocker(User $user): ?string
    {
        $active = Booking::query()->withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->where('booking_time', '>', now())
            ->count();
        if ($active > 0) {
            return "شما {$active} نوبت فعال دارید. پیش از حذف حساب، نوبت‌ها را لغو کنید یا منتظر انجامشان بمانید.";
        }

        $balance = (float) ($user->wallet?->balance ?? 0);
        if ($balance > 0) {
            return 'کیف پول شما '.number_format($balance).' تومان موجودی دارد. پیش از حذف حساب آن را در رزرو بعدی استفاده کنید یا برای تسویه با سالن تماس بگیرید.';
        }

        return null;
    }

    /**
     * @return string deleted (ردیف حذف شد) یا anonymized
     */
    public function delete(User $user): string
    {
        return DB::transaction(function () use ($user) {
            $hasHistory = Booking::query()->withoutGlobalScopes()->where('user_id', $user->id)->exists()
                || PaymentTransaction::where('user_id', $user->id)->exists();

            if (! $hasHistory) {
                $user->delete();

                return 'deleted';
            }

            $user->tokens()->delete();
            DB::table('bot_links')->where('user_id', $user->id)->delete();
            DB::table('user_notifications')->where('notifiable_type', $user->getMorphClass())->where('notifiable_id', $user->id)->delete();
            DB::table('sessions')->where('user_id', $user->id)->delete();

            $user->forceFill([
                'name' => 'مشتری حذف‌شده',
                'phone' => 'deleted-'.$user->id,
                'password' => Hash::make(Str::random(40)),
                'phone_verified_at' => null,
                'two_factor_enabled' => false,
                'two_factor_code' => null,
                'two_factor_code_expires_at' => null,
                'verification_code' => null,
                'verification_code_expire_at' => null,
                'login_verification_code' => null,
                'login_verification_code_expire_at' => null,
                'remember_token' => null,
            ])->save();

            return 'anonymized';
        });
    }
}
