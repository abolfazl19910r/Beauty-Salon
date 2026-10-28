<?php

namespace App\Services\Legal;

use App\Models\LegalAcceptance;
use App\Models\Salon;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * ثبت پذیرش قوانین در ثبت‌نام سالن و مشتری (بسته‌ی مجوز 5د). حساب فقط بعد از تأیید کد پیامکی ساخته می‌شود، پس لحظه‌ی پذیرش
 * (همان فرمی که تیک خورد: زمان، IP، user agent) کنار challenge نگه داشته و بعد از تأیید با کاربر ثبت می‌شود.
 *
 * اپ‌های قدیمی (پیش از app-0023) فیلد accept_terms ندارند: ثبت‌نامشان پذیرفته و پذیرش با منبع «app-implicit» و نسخه‌ی اپ
 * (سرآیند X-App-Version) ثبت می‌شود (تصمیم ۲۰۲۶-۱۰-۲۵).
 */
class LegalAcceptanceRecorder
{
    public const CUSTOMER_DOCUMENTS = ['terms', 'privacy'];

    public const SALON_DOCUMENTS = ['salon_terms', 'privacy'];

    private const TTL_MINUTES = 120;

    /** @return array{at: string, ip: ?string, ua: ?string, source: string, app_version: ?string} */
    public function capture(Request $request, string $source): array
    {
        $appVersion = $request->header('X-App-Version');

        return [
            'at' => now()->toIso8601String(),
            'ip' => $request->ip(),
            'ua' => mb_substr((string) $request->userAgent(), 0, 1000) ?: null,
            'source' => $source,
            'app_version' => is_string($appVersion) && $appVersion !== '' ? mb_substr($appVersion, 0, 32) : null,
        ];
    }

    public function remember(string $flow, string $challenge, array $meta): void
    {
        Cache::put($this->key($flow, $challenge), $meta, now()->addMinutes(self::TTL_MINUTES));
    }

    public function pull(string $flow, string $challenge): ?array
    {
        $meta = Cache::pull($this->key($flow, $challenge));

        return is_array($meta) ? $meta : null;
    }

    /** @param list<string> $documents */
    public function record(?User $user, ?Salon $salon, array $documents, array $meta): void
    {
        foreach ($documents as $document) {
            LegalAcceptance::create([
                'user_id' => $user?->id,
                'salon_id' => $salon?->id,
                'document' => $document,
                'version' => (string) config("legal.versions.{$document}"),
                'source' => $meta['source'] ?? 'web',
                'app_version' => $meta['app_version'] ?? null,
                'ip_address' => $meta['ip'] ?? null,
                'user_agent' => $meta['ua'] ?? null,
                'accepted_at' => $meta['at'] ?? now(),
            ]);
        }
    }

    /**
     * پذیرش دوباره‌ی سندهای در انتظار (صفحه‌ی «قوانین به‌روز شد»، ۲۰۲۶-۱۰-۲۸): همان لحظه و همان درخواست ثبت می‌شود.
     *
     * @param  list<string>  $documents
     */
    public function acceptNow(User $user, ?Salon $salon, array $documents, Request $request, string $source): void
    {
        $this->record($user, $salon, $documents, $this->capture($request, $source));
    }

    private function key(string $flow, string $challenge): string
    {
        return 'legal_acceptance:'.$flow.':'.hash('sha256', $challenge);
    }
}
