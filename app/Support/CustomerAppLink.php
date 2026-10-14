<?php

namespace App\Support;

use App\Models\Salon;

/**
 * دکمه‌ی «باز کردن در اپ ماهرو» روی صفحه‌ی وب سالن (بسته‌ی ۳ب، تصمیم ۲۰۲۶-۱۰-۱۴).
 *
 * QR کارت مدیر (۳الف) آدرس وب سالن است؛ اگر مشتری آن را با دوربین خود گوشی اسکن کند صفحه‌ی وب باز می‌شود، نه اپ.
 * روی اندروید این دکمه با لینک intent کروم اپ را مستقیم روی همان سالن باز می‌کند (mahru://salon/{slug} — صفحه‌ی
 * salon/[slug] اپ). اگر اپ نصب نیست، کروم به browser_fallback_url می‌رود: آدرس دانلود اپ اگر تنظیم شده، وگرنه
 * همین صفحه (بدون آن، کروم مشتری را به صفحه‌ی ناموجود گوگل‌پلی می‌برد).
 * App Links واقعی (لینک https که خودش اپ را باز کند) بعداً.
 */
final class CustomerAppLink
{
    /**
     * @return array{app_url: string, intent_url: string, download_url: ?string}
     */
    public static function forSalon(Salon $salon): array
    {
        $scheme = (string) config('api.customer_app_scheme', 'mahru');
        $package = (string) config('api.customer_app_package', 'ir.mahru');
        $download = config('api.customer_app_download_url') ?: null;
        $path = 'salon/'.rawurlencode($salon->slug);
        $fallback = $download ?? $salon->publicUrl();

        return [
            'app_url' => "{$scheme}://{$path}",
            'intent_url' => "intent://{$path}#Intent;scheme={$scheme};package={$package};S.browser_fallback_url=".rawurlencode($fallback).';end',
            'download_url' => $download,
        ];
    }
}
