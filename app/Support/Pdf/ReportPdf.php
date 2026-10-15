<?php

namespace App\Support\Pdf;

use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

/**
 * سازنده‌ی مشترک mPDF برای گزارش‌های PDF (مدیر و متخصص، ۲۰۲۶-۱۰-۱۵).
 *
 * قبلاً گزارش متخصص `default_font => vazir` می‌گفت ولی فونت را ثبت نمی‌کرد، پس mPDF فونت پیش‌فرض خودش را می‌گذاشت؛ و هر دو
 * قالب `font-family: vazir-bold` داشتند که هیچ‌وقت تعریف نشده بود (پررنگ‌ها عادی چاپ می‌شدند). حالا Vazirmatn با وزن عادی و
 * پررنگ از storage/fonts ثبت می‌شود و قالب‌ها فقط `font-weight: bold` می‌نویسند.
 *
 * mPDF متغیر CSS (var(--...)) نمی‌فهمد؛ رنگ‌های گزارش در partials/pdf-report-style ثابت‌اند.
 */
class ReportPdf
{
    /** @param  array<string, mixed>  $overrides */
    public static function make(array $overrides = []): Mpdf
    {
        $defaultConfig = (new ConfigVariables)->getDefaults();
        $defaultFontConfig = (new FontVariables)->getDefaults();

        $mpdf = new Mpdf(array_merge([
            'mode' => 'utf-8',
            'format' => 'A4',
            'fontDir' => array_merge($defaultConfig['fontDir'], [storage_path('fonts')]),
            'fontdata' => $defaultFontConfig['fontdata'] + [
                'vazir' => ['R' => 'Vazirmatn-Regular.ttf', 'B' => 'Vazirmatn-Bold.ttf', 'useOTL' => 0xFF, 'useKashida' => 75],
            ],
            'default_font' => 'vazir',
            // خاموش: autoLangToFont متن فارسی را به XB Riyaz خود mPDF می‌برد و Vazirmatn نادیده گرفته می‌شد
            'autoScriptToLang' => false,
            'autoLangToFont' => false,
            'margin_left' => 12,
            'margin_right' => 12,
            'margin_top' => 12,
            'margin_bottom' => 18,
            'margin_footer' => 8,
            'tempDir' => sys_get_temp_dir(),
        ], $overrides));

        $mpdf->SetDirectionality('rtl');

        return $mpdf;
    }
}
