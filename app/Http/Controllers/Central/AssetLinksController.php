<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * GET /.well-known/assetlinks.json (بسته‌ی ۵، Android App Links) — روی هر میزبانی (دامنه‌ی اصلی و ساب‌دامین سالن‌ها).
 *
 * اپ مشتری با intent-filter ‏autoVerify برای «*.arisheh.ir» و «arisheh.ir» می‌گوید لینک سالن را باز می‌کند؛ اندروید برای میزبان wildcard
 * همین فایل را از دامنه‌ی اصلی می‌خواند و اگر اثرانگشت امضای اپ نصب‌شده در آن باشد، لینک https سالن مستقیم اپ را باز می‌کند.
 * شرط اندروید: HTTPS، کد ۲۰۰ بدون redirect، Content-Type ‏application/json. اثرانگشت‌ها از config('api.customer_app_sha256_fingerprints').
 */
class AssetLinksController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $fingerprints = self::fingerprints();

        $statements = $fingerprints === [] ? [] : [[
            'relation' => ['delegate_permission/common.handle_all_urls'],
            'target' => [
                'namespace' => 'android_app',
                'package_name' => (string) config('api.customer_app_package'),
                'sha256_cert_fingerprints' => $fingerprints,
            ],
        ]];

        return response()->json($statements, 200, ['Cache-Control' => 'public, max-age=3600'], JSON_UNESCAPED_SLASHES);
    }

    /**
     * «AB:CD:…» با حروف بزرگ؛ شکل بی‌دونقطه (۶۴ رقم هگز، مثل خروجی بعضی ابزارها) هم پذیرفته می‌شود. مقدار نادرست کنار می‌رود و
     * لاگ می‌شود (یک اثرانگشت غلط کل فایل را بی‌اثر نمی‌کند).
     *
     * @return list<string>
     */
    public static function fingerprints(): array
    {
        $out = [];
        foreach ((array) config('api.customer_app_sha256_fingerprints') as $raw) {
            $hex = strtoupper(preg_replace('/[^0-9A-Fa-f]/', '', (string) $raw));
            if (strlen($hex) !== 64) {
                Log::warning('assetlinks: ignored invalid CUSTOMER_APP_SHA256_FINGERPRINTS entry', ['value' => $raw]);

                continue;
            }
            $out[] = implode(':', str_split($hex, 2));
        }

        return array_values(array_unique($out));
    }
}
