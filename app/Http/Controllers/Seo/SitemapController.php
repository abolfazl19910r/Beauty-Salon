<?php

namespace App\Http\Controllers\Seo;

use App\Http\Controllers\Controller;
use App\Models\BeautyService;
use App\Models\BlogPost;
use App\Models\CoursePackage;
use App\Models\Salon;
use App\Models\Specialist;
use App\Support\CentralUrl;
use App\Support\CurrentSalon;
use App\Support\Features\Feature;
use App\Support\Seo\Seo;
use Illuminate\Http\Response;

/**
 * sitemap پویا (سئو، ۲۰۲۶-۱۱-۲۳):
 * - /sitemap.xml دامنه‌ی اصلی = sitemap index: صفحه‌های پلتفرم (/sitemap-pages.xml) + sitemap هر سالن عمومی. property از نوع
 *   Domain در Search Console همه‌ی زیردامنه‌ها را پوشش می‌دهد، پس نشانی‌های زیردامنه در index مجازند.
 * - /sitemap.xml هر سالن (route ‏salon.sitemap، زیر salon.resolve): خانه، خدمات، متخصص‌ها، وبلاگ و دوره‌ها (با امکان پلن).
 * فقط همان صفحه‌هایی که config/seo.php ایندکس‌پذیر می‌داند؛ پنل، ورود و API هرگز.
 */
class SitemapController extends Controller
{
    public function index(): Response
    {
        abort_unless(Seo::enabled(), 404);

        $maps = [CentralUrl::route('seo.sitemap.pages')];

        Salon::query()
            ->where('is_suspended', false)
            ->where('subscription_ends_at', '>', now())
            ->where('verification_status', 'approved')
            ->orderBy('id')
            ->pluck('slug')
            ->each(function (string $slug) use (&$maps) {
                $maps[] = route('salon.sitemap', ['salon_slug' => $slug]);
            });

        $body = '';
        foreach ($maps as $loc) {
            $body .= '<sitemap><loc>'.$this->e($loc).'</loc></sitemap>';
        }

        return $this->xml('<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'.$body.'</sitemapindex>');
    }

    public function pages(): Response
    {
        abort_unless(Seo::enabled(), 404);

        $urls = array_map(fn (string $name) => ['loc' => CentralUrl::route($name)], config('seo.central_routes', []));

        return $this->urlset($urls);
    }

    public function salon(CurrentSalon $currentSalon): Response
    {
        $salon = $currentSalon->get();
        abort_unless(Seo::enabled() && Seo::salonIsPublic($salon), 404);

        $urls = [
            ['loc' => route('home')],
            ['loc' => route('services.index')],
        ];

        BeautyService::query()->orderBy('id')->get(['id', 'updated_at'])
            ->each(function (BeautyService $service) use (&$urls) {
                $urls[] = ['loc' => route('services.show', $service), 'lastmod' => $service->updated_at];
            });

        Specialist::query()->orderBy('id')->get(['id', 'updated_at'])
            ->each(function (Specialist $specialist) use (&$urls) {
                $urls[] = ['loc' => route('specialists.show', $specialist), 'lastmod' => $specialist->updated_at];
            });

        $posts = BlogPost::query()
            ->where('is_published', true)
            ->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->orderBy('id')
            ->get(['id', 'slug', 'updated_at']);
        if ($posts->isNotEmpty()) {
            $urls[] = ['loc' => route('blog.index')];
            foreach ($posts as $post) {
                $urls[] = ['loc' => route('blog.show', ['post' => $post->slug]), 'lastmod' => $post->updated_at];
            }
        }

        if ($salon->hasFeature(Feature::TreatmentCourses)) {
            $urls[] = ['loc' => route('courses.index')];
            CoursePackage::query()->onSale()->get(['id', 'updated_at'])
                ->each(function (CoursePackage $package) use (&$urls) {
                    $urls[] = ['loc' => route('courses.show', $package), 'lastmod' => $package->updated_at];
                });
        }

        return $this->urlset($urls);
    }

    /** @param  array<int, array{loc: string, lastmod?: mixed}>  $urls */
    private function urlset(array $urls): Response
    {
        $body = '';
        foreach ($urls as $url) {
            $body .= '<url><loc>'.$this->e($url['loc']).'</loc>';
            if (! empty($url['lastmod'])) {
                $body .= '<lastmod>'.$url['lastmod']->toAtomString().'</lastmod>';
            }
            $body .= '</url>';
        }

        return $this->xml('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'.$body.'</urlset>');
    }

    private function xml(string $root): Response
    {
        return response('<?xml version="1.0" encoding="UTF-8"?>'."\n".$root."\n", 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    private function e(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
