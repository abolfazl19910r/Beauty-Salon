<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\AppRelease;
use App\Services\Apps\AppReleaseService;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * صفحه‌ی عمومی «دانلود اپ ماهرو» روی دامنه‌ی اصلی (بسته‌ی ۵، ۲۰۲۶-۱۰-۲۰) — اپ‌ها در مارکت نیستند.
 * GET /app: آخرین نسخه‌ی منتشرشده‌ی هر دو اپ + راهنمای «نصب از منبع ناشناس»؛ GET /app/download/{variant}: خود فایل.
 * دکمه‌ی «نصب اپ» سایت هر سالن (و صفحه‌ی /app خود سالن) به همین صفحه می‌آید.
 */
class AppDownloadController extends Controller
{
    public function show(): View
    {
        $latest = [];
        foreach (array_keys(AppRelease::VARIANTS) as $variant) {
            $latest[$variant] = AppRelease::latestFor($variant);
        }

        return view('central.app-download', ['latest' => $latest]);
    }

    public function download(string $variant): StreamedResponse
    {
        abort_unless(array_key_exists($variant, AppRelease::VARIANTS), 404);
        $release = AppRelease::latestFor($variant);
        abort_if($release === null || ! Storage::disk(AppReleaseService::DISK)->exists($release->file_path), 404);

        AppRelease::query()->whereKey($release->id)->increment('download_count');

        return Storage::disk(AppReleaseService::DISK)->download($release->file_path, $release->downloadName(), [
            'Content-Type' => 'application/vnd.android.package-archive',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'no-store',
        ]);
    }
}
