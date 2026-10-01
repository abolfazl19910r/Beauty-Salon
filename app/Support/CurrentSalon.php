<?php

namespace App\Support;

use App\Models\Salon;

/**
 * ⭐ Phase 1 SaaS multi-tenant (feat/saas-multi-tenant-salons, commit 2): registered as a
 * singleton (see AppServiceProvider::register()), so it lives for exactly one request and is
 * shared by every class that resolves it during that request — the salon middleware trio
 * (ResolveSalonFromRoute / EnsureAdminSalonActive) sets it once near the start of the request;
 * everything downstream (BelongsToSalon's global scope, controllers, views) just reads it.
 *
 * Deliberately NOT set at all for super-admin routes — see EnsureSuperAdmin middleware, which
 * never calls set(). BelongsToSalon::bootBelongsToSalon() below only adds its WHERE clause when
 * id() returns a value, so an unset CurrentSalon means "no salon filter" rather than "filter to
 * nothing" — this is what lets the super-admin panel see every salon's data without a single
 * withoutGlobalScope() call scattered through its controllers.
 */
class CurrentSalon
{
    protected ?Salon $salon = null;

    public function set(Salon $salon): void
    {
        $this->salon = $salon;
    }

    public function get(): ?Salon
    {
        return $this->salon;
    }

    public function id(): ?int
    {
        return $this->salon?->id;
    }

    public function clear(): void
    {
        $this->salon = null;
    }

    /** عمق allSalons() تو در تو */
    protected int $allSalonsDepth = 0;

    /**
     * کد داخل $callback صریحاً با همه‌ی سالن‌ها کار می‌کند (پنل سوپرادمین، دستورهای زمان‌بندی‌شده‌ای که روی همه‌ی سالن‌ها
     * می‌گردند). سالن جاری موقتاً برداشته و بعد برگردانده می‌شود. بدون این، کوئری مدل سالن‌دار بدون سالن جاری خطا
     * می‌دهد (حالت سخت‌گیر، ۲۰۲۶-۱۰-۰۱).
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public function allSalons(callable $callback): mixed
    {
        $previous = $this->salon;
        $this->salon = null;
        $this->allSalonsDepth++;

        try {
            return $callback();
        } finally {
            $this->allSalonsDepth--;
            $this->salon = $previous;
        }
    }

    /**
     * $callback با سالن مشخص (مثلاً سالنِ رکوردی که job روی آن کار می‌کند)؛ سالن قبلی بعد از آن برمی‌گردد. شناسه‌ی null یا
     * سالن ناموجود → همان وضعیت فعلی.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public function withSalon(Salon|int|null $salon, callable $callback): mixed
    {
        $salon = is_int($salon) ? Salon::find($salon) : $salon;
        if (! $salon) {
            return $callback();
        }

        $previous = $this->salon;
        $previousDepth = $this->allSalonsDepth;
        $this->salon = $salon;
        $this->allSalonsDepth = 0;

        try {
            return $callback();
        } finally {
            $this->salon = $previous;
            $this->allSalonsDepth = $previousDepth;
        }
    }

    public function allowsAllSalons(): bool
    {
        return $this->allSalonsDepth > 0;
    }

    /**
     * global scope مدل‌های سالن‌دار وقتی سالن جاری نیست: داخل allSalons() بدون فیلتر؛ وگرنه خطا (یا در حالت log فقط هشدار).
     */
    public function guardMissing(string $model): void
    {
        if ($this->allowsAllSalons()) {
            return;
        }

        if (config('tenancy.strict', 'throw') === 'log') {
            \Illuminate\Support\Facades\Log::warning('Salon-scoped query without a current salon', ['model' => $model]);

            return;
        }

        throw \App\Exceptions\MissingSalonContextException::forModel($model);
    }
}
