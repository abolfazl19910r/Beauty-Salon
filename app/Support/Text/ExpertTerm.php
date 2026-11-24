<?php

namespace App\Support\Text;

/**
 * نام کاربرپسند «متخصص» ← «کارشناس / کارشناسان» (بسته‌ی ۰ به‌روزرسانی ظاهری، تصمیم ۲۰۲۶-۱۱-۲۴). فقط متن؛ نام کلاس، جدول،
 * ستون، route و کلید API همان specialist می‌ماند. «متخصص‌ترین» (صفت، نه نقش) دست نمی‌خورد.
 */
final class ExpertTerm
{
    public const OLD = 'متخصص';

    public const NEW = 'کارشناس';

    public const PLURAL = 'کارشناسان';

    private const MAP = [
        'متخصص‌هایی' => 'کارشناسانی',
        'متخصص‌هاش' => 'کارشناسانش',
        'متخصص‌های' => 'کارشناسان',
        'متخصص‌ها' => 'کارشناسان',
        'متخصصین' => 'کارشناسان',
        'متخصصان' => 'کارشناسان',
        'متخصص‌تان' => 'کارشناس‌تان',
        'متخصص' => 'کارشناس',
    ];

    public static function replace(?string $text): ?string
    {
        if ($text === null || ! str_contains($text, self::OLD)) {
            return $text;
        }

        $text = str_replace('متخصص‌ترین', "\u{E000}", $text);

        return str_replace("\u{E000}", 'متخصص‌ترین', strtr($text, self::MAP));
    }
}
