<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\BeautyServiceRepositoryInterface;
use App\Repositories\Contracts\SpecialistRepositoryInterface;
use App\Support\CurrentSalon;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(
        protected CurrentSalon $currentSalon,
        protected BeautyServiceRepositoryInterface $beautyServiceRepository,
        protected SpecialistRepositoryInterface $specialistRepository,
    ) {}

    public function index(): View
    {
        // ⭐ Fix (real, confirmed cross-tenant data leak — found while testing محور «۳» with two
        // salons side by side): these cache keys used to be the literal strings 'home_services'/
        // 'home_specialists' with NO salon identifier in them, even though BeautyService and
        // Specialist are both salon-scoped models (BelongsToSalon). Whichever salon's homepage
        // was visited FIRST populated the cache for 30 minutes, and every OTHER salon's homepage
        // served that same cached result during that window — confirmed directly: salon A's
        // services showing up on salon B's homepage. This route is always reached through
        // 'salon.resolve' middleware (routes/web.php), so CurrentSalon is reliably bound here;
        // suffixing the key with its id is enough to give every salon its own cache bucket.
        $salonId = $this->currentSalon->id();

        $services = Cache::remember("home_services:{$salonId}", 1800, function () {
            return $this->beautyServiceRepository->query()
                ->latest()
                ->select('id', 'name', 'slug', 'description', 'price', 'duration', 'image', 'category_id')
                ->take(6)
                ->get();
        });

        $specialists = Cache::remember("home_specialists:{$salonId}", 1800, function () {
            return $this->specialistRepository->query()
                ->latest()
                ->with(['schedules' => fn ($q) => $q->where('is_active', true)->orderBy('day_of_week')])
                ->select('id', 'name', 'email', 'phone', 'user_id', 'photo_path')
                ->take(4)
                ->get();
        });

        // ⭐ بسته‌ی «و»: هر چیدمان قالب سایت صفحه‌ی خانه‌ی خودش را دارد (salon-home/*)؛ پیش‌فرض همان home
        $appearance = app(\App\Support\Themes\CurrentAppearance::class)->get();
        if ($appearance->isDefaultSite()) {
            // ⭐ بسته‌ی مجوز 5د: فقط داده‌ی واقعی همین سالن (پیش‌تر نظر، آمار و عکس ساختگی وقتی داده نبود — تبلیغ فریبنده)
            $honest = Cache::remember("home_default_honest:{$salonId}", 1800, fn () => [
                'stats' => [
                    'customers' => \App\Models\Booking::query()->where('status', 'completed')->distinct()->count('user_id'),
                    'specialists' => $this->specialistRepository->query()->count(),
                    'reviews' => \App\Models\Review::query()->where('is_approved', true)->count(),
                    'rating' => round((float) \App\Models\Review::query()->where('is_approved', true)->avg('overall_rating'), 1),
                ],
                'testimonials' => \App\Models\Review::query()->where('is_approved', true)
                    ->whereNotNull('comment')->where('comment', '!=', '')
                    ->with('user:id,name')->latest('id')->take(6)->get()
                    ->map(fn (\App\Models\Review $r) => (object) [
                        // حریم خصوصی: نام کوچک و حرف اول نام خانوادگی
                        'name' => self::shortName((string) $r->user?->name),
                        'rating' => (int) $r->overall_rating,
                        'text' => (string) $r->comment,
                    ])->all(),
                'galleryImages' => \App\Models\GalleryImage::query()->where('is_active', true)->orderBy('order')->take(10)->get()
                    ->map(fn (\App\Models\GalleryImage $g) => (object) ['image' => $g->image_url, 'instagram_link' => null])->all(),
            ]);

            return view('home', compact('services', 'specialists') + $honest);
        }

        // آمار واقعی نظرهای تأییدشده (نه عدد ساختگی): اگر نظری نیست، چیدمان‌ها کاشی امتیاز را نشان نمی‌دهند
        $reviewStats = Cache::remember("home_reviews:{$salonId}", 1800, function () {
            $query = \App\Models\Review::query()->where('is_approved', true);

            return ['count' => (clone $query)->count(), 'avg' => round((float) (clone $query)->avg('overall_rating'), 1)];
        });

        return view($appearance->homeView(), compact('services', 'specialists', 'reviewStats'));
    }

    private static function shortName(string $name): string
    {
        $parts = preg_split('/\s+/u', trim($name)) ?: [];
        if ($parts === [] || $parts[0] === '') {
            return 'مشتری';
        }

        return count($parts) > 1 ? $parts[0].' '.mb_substr(end($parts), 0, 1).'.' : $parts[0];
    }
}
