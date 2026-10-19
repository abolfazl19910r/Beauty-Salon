<?php

namespace App\Support;

use App\Models\Salon;

/**
 * آدرس سالن برای نمایش کوتاه در فهرست‌ها (بسته‌ی «ح»، ۵): همان Salon::publicUrl() بدون http(s):// و / آخر —
 * با CENTRAL_DOMAIN «rasta.mahru.ir» (یا محلی «rasta.192.168.1.2.nip.io:8000»)، بدون آن «127.0.0.1:8000/s/rasta».
 * پیش‌تر پنل ابرمدیر همیشه «/s/rasta» دستی می‌نوشت، حتی وقتی سالن ساب‌دامین داشت.
 */
final class SalonAddress
{
    public static function short(Salon $salon): string
    {
        return rtrim((string) preg_replace('#^https?://#', '', $salon->publicUrl()), '/');
    }
}
