<?php

namespace App\Services\Api;

use App\Exceptions\Api\ApiException;
use App\Models\Salon;
use App\Models\Specialist;
use App\Models\User;
use App\Support\CurrentSalon;

/**
 * چه کسی با کدام اپ وارد می‌شود (بسته‌ی ۱ اپلیکیشن، تصمیم‌های ۲۰۲۶-۱۰-۱۰):
 *  - «ماهرو» (customer): فقط حساب مشتری؛ سالن همان salon_id حساب (حساب مشتری در هر سالن جداست).
 *  - «ماهرو همکار» (staff): فقط متخصص — حساب کادر با رکورد متخصص حذف‌نشده. مدیر/سوپرادمین بدون رکورد
 *    متخصص wrong_app می‌گیرد (پنل مدیر فعلاً وب است).
 * سالن معلق یا با اشتراک تمام‌شده → salon_inactive. هم در ورود (پیش از فرستادن پیامک) و هم در هر درخواست
 * واردشده (EnsureApiAudience) صدا زده می‌شود، تا تعلیق سالن بلافاصله اثر کند بدون حذف توکن‌ها.
 */
class ApiAudienceGate
{
    public const CUSTOMER = 'customer';

    public const STAFF = 'staff';

    public const AUDIENCES = [self::CUSTOMER, self::STAFF];

    public function __construct(protected readonly CurrentSalon $currentSalon) {}

    /**
     * @return array{salon: Salon, specialist: ?Specialist}
     */
    public function resolve(User $user, string $audience): array
    {
        return $audience === self::STAFF
            ? $this->resolveStaff($user)
            : ['salon' => $this->resolveCustomerSalon($user), 'specialist' => null];
    }

    protected function resolveCustomerSalon(User $user): Salon
    {
        if ($user->user_type !== 'customer' || ! $user->salon_id) {
            throw ApiException::wrongApp();
        }

        $salon = Salon::find($user->salon_id);

        return $this->ensureActive($salon);
    }

    /**
     * @return array{salon: Salon, specialist: Specialist}
     */
    protected function resolveStaff(User $user): array
    {
        if ($user->user_type !== 'staff') {
            throw ApiException::wrongApp();
        }

        // رکورد متخصص هنوز سالن جاری ندارد (حالت سخت‌گیر BelongsToSalon) — مثل EnsureSpecialistSalonActive
        $specialist = $this->currentSalon->allSalons(fn () => $user->specialist()->first());

        if (! $specialist) {
            throw ApiException::wrongApp();
        }

        return ['salon' => $this->ensureActive(Salon::find($specialist->salon_id)), 'specialist' => $specialist];
    }

    protected function ensureActive(?Salon $salon): Salon
    {
        if (! $salon || ! $salon->hasActiveSubscription()) {
            throw ApiException::salonInactive();
        }

        return $salon;
    }
}
