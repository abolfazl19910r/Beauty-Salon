<?php

namespace App\Http\Controllers\Seo;

use App\Http\Controllers\Controller;
use App\Support\CentralUrl;
use App\Support\CurrentSalon;
use App\Support\Seo\Seo;
use Illuminate\Http\Response;

/**
 * robots.txt پویا برای هر میزبان (سئو، ۲۰۲۶-۱۱-۲۳): دامنه‌ی اصلی و هر زیردامنه‌ی سالن robots و sitemap خودش را دارد.
 * public/robots.txt ثابت حذف شد (برای همه‌ی میزبان‌ها یکی بود و sitemap نداشت)؛ nginx ‏/robots.txt را به Laravel می‌دهد.
 */
class RobotsController extends Controller
{
    public function __invoke(CurrentSalon $currentSalon): Response
    {
        $salon = $currentSalon->get();

        if (! Seo::enabled() || ($salon !== null && ! Seo::salonIsPublic($salon))) {
            return $this->text("User-agent: *\nDisallow: /\n");
        }

        $lines = ['User-agent: *', 'Allow: /'];
        foreach (config($salon ? 'seo.disallow.salon' : 'seo.disallow.central', []) as $path) {
            $lines[] = 'Disallow: '.$path;
        }
        $lines[] = '';
        $lines[] = 'Sitemap: '.($salon ? route('salon.sitemap') : CentralUrl::route('seo.sitemap'));

        return $this->text(implode("\n", $lines)."\n");
    }

    private function text(string $body): Response
    {
        return response($body, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
