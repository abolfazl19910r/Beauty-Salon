<?php

namespace App\Services\Auth;

use App\Jobs\SendLoginVerificationCodeJob;
use App\Models\Salon;
use App\Models\User;
use App\Support\Queues;
use Illuminate\Support\Collection;

/**
 * بسته‌ی «ح»، ۱۲ (۲۰۲۶-۱۰-۲۰) — صفحه‌ی «سالن‌های من» در دامنه‌ی اصلی: شماره ← کد پیامکی ← فهرست سالن‌هایی که این شماره در
 * آن‌ها حساب مشتری دارد ← ورود همان سالن با شماره‌ی پرشده. نشست مشترک ساخته نمی‌شود؛ فهرست فقط بعد از کد درست نشان داده می‌شود.
 *
 * شماره‌ای که هیچ حسابی ندارد پیامک نمی‌گیرد (هزینه‌ی پلتفرم) ولی پاسخ صفحه همان است («اگر حسابی داشته باشید کد می‌آید»)
 * تا از بیرون نشود فهمید شماره‌ای مشتری کجاست.
 */
class MySalonsChallenge extends SmsCodeChallenge
{
    protected function prefix(): string
    {
        return 'my-salons:';
    }

    protected function deliver(array $data, string $code): void
    {
        Queues::dispatchOtp(new SendLoginVerificationCodeJob((int) $data['user_id'], $code));
    }

    protected function expiredMessage(): string
    {
        return 'مهلت کد تمام شده است. دوباره شماره را وارد کنید.';
    }

    /** اولین حساب مشتری این شماره (فقط برای فرستادن پیامک)؛ null = هیچ */
    public function firstCustomer(string $phone): ?User
    {
        return User::withoutGlobalScopes()->where('phone', $phone)->where('user_type', 'customer')->orderBy('id')->first();
    }

    /** سالن‌های باز (معلق یا منقضی نیستند) که این شماره در آن‌ها حساب مشتری دارد */
    public function salonsFor(string $phone): Collection
    {
        $ids = User::withoutGlobalScopes()->where('phone', $phone)->where('user_type', 'customer')->whereNotNull('salon_id')->pluck('salon_id');

        return Salon::withoutGlobalScopes()->whereIn('id', $ids)
            ->where('is_suspended', false)->where('subscription_ends_at', '>', now())
            ->orderBy('name')->get();
    }
}
