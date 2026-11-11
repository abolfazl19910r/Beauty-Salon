<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Support\Legal\DomainVerificationFile;
use Illuminate\Http\Response;

/**
 * GET /{نام}.txt — فایل تأیید مالکیت دامنه (اینماد، ۲۰۲۶-۱۱-۱۱). بایت‌به‌بایت همان چیزی که ابرمدیر بارگذاری کرده، text/plain، کد ۲۰۰
 * بی redirect؛ هر نام دیگری ۴۰۴ (حتی وقتی فایلی ذخیره شده)، تا route حدس‌زدنی نشود.
 */
class DomainVerificationFileController extends Controller
{
    public function __invoke(string $verificationFile): Response
    {
        $content = DomainVerificationFile::contentFor($verificationFile);
        abort_if($content === null, 404);

        return response($content, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'no-cache',
            'X-Robots-Tag' => 'noindex',
        ]);
    }
}
