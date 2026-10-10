<?php

namespace App\Services;

use App\Support\CurrentSalon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ReportCacheService
{
    protected int $cacheTtl;

    protected bool $cacheEnabled;

    protected string $cachePrefix;

    public function __construct(protected CurrentSalon $currentSalon)
    {
        $this->cacheEnabled = config('cache.reports.enabled', true);
        $this->cacheTtl = config('cache.reports.ttl', 60); // دقیقه
        $this->cachePrefix = config('cache.reports.prefix', 'report_');
    }

    public function remember(string $key, \Closure $callback, ?int $ttl = null): mixed
    {
        if (! $this->cacheEnabled) {
            return $callback();
        }

        $cacheKey = $this->generateCacheKey($key);
        $ttl = $ttl ?: $this->cacheTtl;

        return Cache::remember($cacheKey, $ttl * 60, function () use ($callback) {
            return $callback();
        });
    }

    public function has(string $key): bool
    {
        if (! $this->cacheEnabled) {
            return false;
        }

        return Cache::has($this->generateCacheKey($key));
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (! $this->cacheEnabled) {
            return $default;
        }

        return Cache::get($this->generateCacheKey($key), $default);
    }

    public function put(string $key, mixed $value, ?int $ttl = null): bool
    {
        if (! $this->cacheEnabled) {
            return false;
        }

        $ttl = $ttl ?: $this->cacheTtl;

        return Cache::put($this->generateCacheKey($key), $value, $ttl * 60);
    }

    public function forget(string $key): bool
    {
        if (! $this->cacheEnabled) {
            return false;
        }

        return Cache::forget($this->generateCacheKey($key));
    }

    /**
     * همه‌ی گزارش‌های کش‌شده را باطل می‌کند — با بالا بردن «نسل» کلیدها، نه با پیمایش store.
     *
     * نسخه‌ی قبلی فقط روی store تگ‌دار (array/redis) کار می‌کرد و روی file/database با
     * Error (نه Exception) از کار می‌افتاد: Call to undefined method FileStore::all()
     * — و چون از BookingObserver صدا زده می‌شود، تأیید/لغو نوبت را با ۵۰۰ می‌شکست.
     * روش نسل روی همه‌ی storeها کار می‌کند؛ کلیدهای نسل قبل با TTL خودشان پاک می‌شوند.
     * خطای کش هرگز نباید عملیات نوبت را بشکند، پس Throwable گرفته و لاگ می‌شود.
     */
    public function flush(): bool
    {
        if (! $this->cacheEnabled) {
            return false;
        }

        try {
            return Cache::forever($this->generationKey(), (string) Str::ulid());
        } catch (\Throwable $e) {
            Log::warning('Report cache flush failed', ['error' => $e->getMessage()]);

            return false;
        }
    }

    protected function generation(): string
    {
        try {
            return (string) Cache::get($this->generationKey(), '0');
        } catch (\Throwable) {
            return '0';
        }
    }

    protected function generationKey(): string
    {
        return $this->cachePrefix.'generation';
    }

    protected function generateCacheKey(string $key): string
    {
        // ⭐ Fix (preventive, ۲۰۲۶-۰۹-۱۹ — پیگیری محور «۳»): remember()/put() این کلاس فعلاً
        // هیچ‌جای کدبیس واقعاً صدا زده نمی‌شن (فقط flush() از Observerها) — یعنی امروز این یک
        // نشتی فعال نیست. ولی اگه/وقتی در آینده برای کش گزارش‌های ادمین واقعاً استفاده بشه، بدون
        // این fix دقیقاً همون باگ HomeController رو تکرار می‌کرد (کلید کش مشترک بین همه‌ی
        // سالن‌ها). CurrentSalon()->id() می‌تونه null باشه (مثلاً یک context بدون سالن مشخص)؛
        // در اون حالت هم هنوز deterministic و بی‌خطره (فقط یک namespace مشترک برای «بدون سالن»).
        return $this->cachePrefix.$this->generation().':'.($this->currentSalon->id() ?? 'none').':'.$key;
    }
}
