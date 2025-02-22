<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class ReportCacheService
{
    protected int $defaultTtl = 3600;

    public function remember(string $key, array $params, \Closure $callback, $ttl = null)
    {
        $cacheKey = $this->generateCacheKey($key, $params);
        return Cache::remember($cacheKey, $ttl ?? $this->defaultTtl, $callback);
    }

    public function flush(string $key = null)
    {
        if ($key) {
            $pattern = "reports:{$key}:*";
            $keys = Cache::getRedis()->keys($pattern);
            foreach ($keys as $key) {
                Cache::forget($key);
            }
        } else {
            Cache::tags(['reports'])->flush();
        }
    }

    protected function generateCacheKey(string $key, array $params): string
    {
        $paramString = collect($params)
            ->map(fn($value, $key) => "{$key}:{$value}")
            ->implode('_');

        return "reports:{$key}:{$paramString}";
    }
}
