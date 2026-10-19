<?php

if (! function_exists('jalali_date')) {
    function jalali_date($date, $format = 'Y/m/d'): array|string
    {
        $date = is_string($date) ? \Carbon\Carbon::parse($date) : $date;

        $gregorian_year = $date->year;
        $gregorian_month = $date->month;
        $gregorian_day = $date->day;

        [$jalali_year, $jalali_month, $jalali_day] = gregorian_to_jalali($gregorian_year, $gregorian_month, $gregorian_day);

        // نویسه‌به‌نویسه مثل date() — پیش‌تر فقط Y/m/d با str_replace جایگزین می‌شد و «Y/m/d H:i» در فاکتور ابرمدیر، لاگ‌های امنیتی و
        // صورتحساب «۱۴۰۵/۰۷/۲۷ H:i» خام نشان می‌داد (بسته‌ی «ح»، ۴ و ۹). بک‌اسلش نویسه‌ی بعدی را دست‌نخورده می‌گذارد.
        $parts = [
            'Y' => (string) $jalali_year,
            'y' => substr((string) $jalali_year, -2),
            'm' => str_pad((string) $jalali_month, 2, '0', STR_PAD_LEFT),
            'n' => (string) $jalali_month,
            'd' => str_pad((string) $jalali_day, 2, '0', STR_PAD_LEFT),
            'j' => (string) $jalali_day,
            'H' => $date->format('H'),
            'G' => $date->format('G'),
            'i' => $date->format('i'),
            's' => $date->format('s'),
        ];

        $result = '';
        $length = mb_strlen($format);
        for ($i = 0; $i < $length; $i++) {
            $char = mb_substr($format, $i, 1);
            if ($char === '\\' && $i + 1 < $length) {
                $result .= mb_substr($format, ++$i, 1);
            } else {
                $result .= $parts[$char] ?? $char;
            }
        }

        return $result;
    }
}

if (! function_exists('gregorian_to_jalali')) {
    function gregorian_to_jalali($g_y, $g_m, $g_d): array
    {
        $g_days_in_month = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        $j_days_in_month = [31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29];

        $gy = $g_y - 1600;
        $gm = $g_m - 1;
        $gd = $g_d - 1;

        $g_day_no = 365 * $gy + intdiv($gy + 3, 4) - intdiv($gy + 99, 100) + intdiv($gy + 399, 400);

        for ($i = 0; $i < $gm; $i++) {
            $g_day_no += $g_days_in_month[$i];
        }

        if ($gm > 1 && (($gy % 4 == 0 && $gy % 100 != 0) || ($gy % 400 == 0))) {
            $g_day_no++;
        }

        $g_day_no += $gd;

        $j_day_no = $g_day_no - 79;

        $j_np = intdiv($j_day_no, 12053);
        $j_day_no = $j_day_no % 12053;

        $jy = 979 + 33 * $j_np + 4 * intdiv($j_day_no, 1461);

        $j_day_no %= 1461;

        if ($j_day_no >= 366) {
            $jy += intdiv($j_day_no - 1, 365);
            $j_day_no = ($j_day_no - 1) % 365;
        }

        for ($i = 0; $i < 11 && $j_day_no >= $j_days_in_month[$i]; $i++) {
            $j_day_no -= $j_days_in_month[$i];
        }

        $jm = $i + 1;
        $jd = $j_day_no + 1;

        return [$jy, $jm, $jd];
    }
}

if (! function_exists('to_persian_num')) {
    function to_persian_num($number): string
    {
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

        return str_replace($english, $persian, $number);
    }
}
