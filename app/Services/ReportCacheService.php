<?php

namespace App\Services;

use App\Support\CurrentSalon;
use Illuminate\Support\Facades\Cache;

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

    public function flush(): bool
    {
        if (! $this->cacheEnabled) {
            return false;
        }

        try {
            if (method_exists(Cache::getStore(), 'tags')) {
                Cache::tags('reports')->flush();

                return true;
            }

            $keys = collect(Cache::getStore()->all())->keys()->filter(function ($key) {
                return str_starts_with($key, $this->cachePrefix);
            });

            foreach ($keys as $key) {
                Cache::forget($key);
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function generateCacheKey(string $key): string
    {
        // ⭐ Fix (preventive, ۲۰۲۶-۰۹-۱۹ — پیگیری محور «۳»): remember()/put() این کلاس فعلاً
        // هیچ‌جای کدبیس واقعاً صدا زده نمی‌شن (فقط flush() از Observerها) — یعنی امروز این یک
        // نشتی فعال نیست. ولی اگه/وقتی در آینده برای کش گزارش‌های ادمین واقعاً استفاده بشه، بدون
        // این fix دقیقاً همون باگ HomeController رو تکرار می‌کرد (کلید کش مشترک بین همه‌ی
        // سالن‌ها). CurrentSalon()->id() می‌تونه null باشه (مثلاً یک context بدون سالن مشخص)؛
        // در اون حالت هم هنوز deterministic و بی‌خطره (فقط یک namespace مشترک برای «بدون سالن»).
        return $this->cachePrefix.($this->currentSalon->id() ?? 'none').':'.$key;
    }
}
