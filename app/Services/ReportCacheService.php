<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class ReportCacheService
{
    /**
     *
     * @var int
     */
    protected int $cacheTtl;

    /**
     *
     * @var bool
     */
    protected bool $cacheEnabled;

    /**
     *
     * @var string
     */
    protected string $cachePrefix;

    /**
     *
     * @return void
     */
    public function __construct()
    {
        $this->cacheEnabled = config('cache.reports.enabled', true);
        $this->cacheTtl = config('cache.reports.ttl', 60); // دقیقه
        $this->cachePrefix = config('cache.reports.prefix', 'report_');
    }

    /**
     *
     * @param string $key
     * @param \Closure $callback
     * @param int|null $ttl
     * @return mixed
     */
    public function remember(string $key, \Closure $callback, ?int $ttl = null): mixed
    {
        if (!$this->cacheEnabled) {
            return $callback();
        }

        $cacheKey = $this->generateCacheKey($key);
        $ttl = $ttl ?: $this->cacheTtl;

        return Cache::remember($cacheKey, $ttl * 60, function () use ($callback) {
            return $callback();
        });
    }

    /**
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        if (!$this->cacheEnabled) {
            return false;
        }

        return Cache::has($this->generateCacheKey($key));
    }

    /**
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if (!$this->cacheEnabled) {
            return $default;
        }

        return Cache::get($this->generateCacheKey($key), $default);
    }

    /**
     *
     * @param string $key
     * @param mixed $value
     * @param int|null $ttl
     * @return bool
     */
    public function put(string $key, mixed $value, ?int $ttl = null): bool
    {
        if (!$this->cacheEnabled) {
            return false;
        }

        $ttl = $ttl ?: $this->cacheTtl;
        return Cache::put($this->generateCacheKey($key), $value, $ttl * 60);
    }

    /**
     *
     * @param string $key
     * @return bool
     */
    public function forget(string $key): bool
    {
        if (!$this->cacheEnabled) {
            return false;
        }

        return Cache::forget($this->generateCacheKey($key));
    }

    /**
     *
     * @return bool
     */
    public function flush(): bool
    {
        if (!$this->cacheEnabled) {
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

    /**
     *
     * @param string $key
     * @return string
     */
    protected function generateCacheKey(string $key): string
    {
        return $this->cachePrefix . $key;
    }
}
