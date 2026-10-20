<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Support\CurrentSalon;
use App\Support\CustomerAppLink;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * صفحه‌ی «اپ» هر سالن (/app روی آدرس سالن، بسته‌ی ۵ و ۶): اندروید ← باز کردن در اپ یا دانلود APK + کد سالن؛ آیفون ← راهنمای
 * «افزودن به صفحه‌ی اصلی» (PWA)؛ کامپیوتر ← QR همین صفحه برای باز کردن روی گوشی. بخش پیش‌فرض از User-Agent، و اسکریپت صفحه دقیقش
 * می‌کند (iPadOS خودش را مک معرفی می‌کند).
 */
class SalonAppController extends Controller
{
    public function __invoke(Request $request, CurrentSalon $currentSalon): View
    {
        $salon = $currentSalon->get();
        abort_if($salon === null, 404);

        return view('salon-app.install', [
            'salon' => $salon,
            'links' => CustomerAppLink::forSalon($salon),
            'platform' => self::platform((string) $request->userAgent()),
        ]);
    }

    public static function platform(string $userAgent): string
    {
        return match (true) {
            (bool) preg_match('/Android/i', $userAgent) => 'android',
            (bool) preg_match('/iPhone|iPad|iPod/i', $userAgent) => 'ios',
            default => 'desktop',
        };
    }
}
