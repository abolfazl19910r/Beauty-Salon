<?php

namespace App\Support;

use App\Exceptions\IdempotencyKeyReusedException;
use App\Models\IdempotencyKey;

/**
 * Idempotency فرم‌ها (۲۰۲۶-۱۰-۰۱): فرم یک UUID مخفی (idempotency_key) دارد که با هر بار باز شدن صفحه عوض می‌شود. دوبار
 * ارسال همان فرم (دابل‌کلیک، رفرش، رتری شبکه) نتیجه‌ی اول را برمی‌گرداند، نه رکورد دوم.
 *
 * - existing(): پیش از قفل/تراکنش — تکرار بعد از commit اولی سریع برمی‌گردد.
 * - claim(): داخل تراکنش. اگر کار با قفل ردیف سریالی شده (برداشت)، اول دوباره existing() و بعد claim با شناسه‌ی رکورد؛
 *   اگر قفلی نیست (نوبت)، claim را اول تراکنش بزن: درج دوم با همان کلید روی ایندکس یکتا منتظر اولی می‌ماند و بعد
 *   UniqueConstraintViolationException می‌گیرد — فراخواننده رکورد اولی را با existing() برمی‌گرداند.
 * - کلید خالی (کلاینت قدیمی) = رفتار قبلی بدون idempotency. همان کلید با داده‌ی دیگر = IdempotencyKeyReusedException.
 */
final class Idempotency
{
    public const TTL_HOURS = 24;

    public const WITHDRAWAL = 'withdrawal';

    public const BOOKING = 'booking';

    public static function fingerprint(array $data): string
    {
        ksort($data);

        return hash('sha256', json_encode(array_map(fn ($v) => is_numeric($v) ? (string) (0 + $v) : (string) $v, $data)));
    }

    /** شناسه‌ی رکوردی که همین کلید قبلاً ساخته؛ null اگر کلیدی نیست یا هنوز ثبت نشده. */
    public static function existing(string $scope, int $ownerId, ?string $key, string $fingerprint): ?int
    {
        if ($key === null || $key === '') {
            return null;
        }

        $row = IdempotencyKey::where('scope', $scope)->where('owner_id', $ownerId)->where('key', $key)
            ->where('created_at', '>=', now()->subHours(self::TTL_HOURS))
            ->first();

        if (! $row) {
            return null;
        }

        if (! hash_equals($row->fingerprint, $fingerprint)) {
            throw new IdempotencyKeyReusedException;
        }

        return $row->resource_id;
    }

    public static function claim(string $scope, int $ownerId, ?string $key, string $fingerprint, ?int $resourceId = null): ?IdempotencyKey
    {
        if ($key === null || $key === '') {
            return null;
        }

        // کلید منقضی همان مالک: پاک شود تا ایندکس یکتا جلوی استفاده‌ی دوباره را نگیرد
        IdempotencyKey::where('scope', $scope)->where('owner_id', $ownerId)->where('key', $key)
            ->where('created_at', '<', now()->subHours(self::TTL_HOURS))->delete();

        return IdempotencyKey::create([
            'scope' => $scope, 'owner_id' => $ownerId, 'key' => $key, 'fingerprint' => $fingerprint, 'resource_id' => $resourceId,
        ]);
    }

    public static function complete(?IdempotencyKey $row, int $resourceId): void
    {
        $row?->update(['resource_id' => $resourceId]);
    }

    /** قاعده‌ی اعتبارسنجی مشترک فرم‌ها */
    public static function rule(): array
    {
        return ['nullable', 'uuid'];
    }
}
