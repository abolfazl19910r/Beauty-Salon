<?php

namespace App\Services\Salon;

use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\Category;
use App\Models\CoursePackage;
use App\Models\DiscountCode;
use App\Models\GalleryImage;
use App\Models\Review;
use App\Models\Reward;
use App\Models\Salon;
use App\Models\Specialist;
use App\Services\Booking\SalonFreeSlots;
use App\Support\Fa;
use App\Support\Features\Feature;
use App\Support\SalonWorkingHours;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * ⭐ بسته‌ی ۵الف/۵ب: داده‌ی صفحه‌ی خانه‌ی قالب‌های «شاهکار»، «اپ»، «نمایشگاه» و «روزنامه‌ی سالن» (قاعده‌ی ۴ بوم: فقط داده‌ی واقعی؛ نبود هر تکه ← آن تکه پنهان).
 * فهرست خدمات و کارشناسان صفحه از HomeController می‌آید؛ اینجا فقط چیزهایی که این دو قالب بیشتر می‌خواهند.
 */
final class TemplateHomeData
{
    /** با تغییر کدهای تخفیف (AdminDiscountCodeService) کارت «پیشنهاد ویژه» تازه شود. */
    public static function forgetEditorial(int $salonId): void
    {
        Cache::forget("home_editorial:{$salonId}");
    }

    /** قاب «نمایشگاه» حداکثر این تعداد نمونه‌کار را ورق می‌زند. */
    public const SHOWCASE_FRAMES = 8;

    /** چند رکورد اول گالری برای پیدا کردن عکس‌هایی که فایلشان هست بررسی می‌شود. */
    private const GALLERY_SCAN = 200;

    /** با هر تغییر گالری (AdminGalleryController) — شمار نمونه‌کار، کاور جایگزین و قاب «نمایشگاه» تازه شوند. */
    public static function forgetGallery(int $salonId): void
    {
        Cache::forget("home_template_catalog:v2:{$salonId}");
        Cache::forget("home_showcase_gallery:{$salonId}");
    }

    public function __construct(private readonly SalonFreeSlots $freeSlots) {}

    /**
     * @param  Collection<int, BeautyService>  $services  خدمات صفحه‌ی خانه (HomeController)
     * @param  Collection<int, Specialist>  $specialists
     * @return array<string, mixed>
     */
    public function for(Salon $salon, string $layout, Collection $services, Collection $specialists): array
    {
        $catalog = Cache::remember("home_template_catalog:v2:{$salon->id}", 600, fn () => [
            'all' => BeautyService::query()->orderBy('name')->get(['id', 'name', 'price', 'duration', 'category_id', 'image'])
                ->map(fn (BeautyService $s) => ['id' => (int) $s->id, 'name' => (string) $s->name, 'price' => (float) $s->price, 'duration' => (int) $s->duration, 'category_id' => $s->category_id, 'image' => $s->image_url])
                ->all(),
            'categories' => Category::query()->pluck('name', 'id')->all(),
            'popular' => Booking::query()->whereIn('status', ['completed', 'confirmed'])
                ->selectRaw('service_id, count(*) as c')->groupBy('service_id')->orderByDesc('c')->value('service_id'),
            'gallery' => GalleryImage::query()->where('is_active', true)->count(),
            // بسته‌ی ۵ب: «N نمونه‌کار» کنار هر خدمت («نمایشگاه») و جایگزین کاور (اولین عکس گالری)
            'galleryByService' => GalleryImage::query()->where('is_active', true)->whereNotNull('service_id')
                ->selectRaw('service_id, count(*) as c')->groupBy('service_id')->pluck('c', 'service_id')
                ->map(fn ($c) => (int) $c)->all(),
            'galleryFirst' => self::existingGallery(1)->first()?->image_path,
            'specialists' => Specialist::query()->count(),
        ]);

        $all = collect($catalog['all']);
        $defaultId = $all->firstWhere('id', (int) $catalog['popular'])['id'] ?? $all->first()['id'] ?? null;
        $default = $defaultId ? BeautyService::query()->find($defaultId) : null;

        $data = [
            'allServices' => $all,
            'categories' => $catalog['categories'],
            'serviceCount' => $all->count(),
            'specialistCount' => (int) $catalog['specialists'],
            'galleryCount' => (int) $catalog['gallery'],
            'today' => SalonWorkingHours::today($salon->working_hours),
            'nextFree' => $this->freeSlots->nextForSpecialists($salon, $specialists),
            // خدمات صفحه به ترتیب دسته (منوی خدمات «شاهکار»، کاشی‌های «اپ»)
            'menu' => $services->groupBy(fn ($s) => (int) $s->category_id)
                ->map(fn ($group, $categoryId) => ['name' => $catalog['categories'][$categoryId] ?? 'خدمات', 'services' => $group->values()])
                ->values(),
        ];

        if ($layout === 'signature') {
            $data['defaultService'] = $default;
            $data['bookingDays'] = $default ? $this->freeSlots->daysForService($salon, $default) : [];
            $data['serviceSpecialists'] = $default ? $default->specialists()->orderBy('specialists.id')->get(['specialists.id', 'specialists.name']) : collect();
        }
        if (in_array($layout, ['showcase', 'editorial'], true)) {
            // کاور: عکس کاور سالن ← اولین عکس گالری ← null (طرح پالت در نما)
            $data['cover'] = $salon->coverUrl() ?: ($catalog['galleryFirst'] ? asset('storage/'.$catalog['galleryFirst']) : null);
        }
        if ($layout === 'showcase') {
            $data['nearest'] = $this->freeSlots->nearest($salon, 1)[0] ?? null;
            $data['galleryByService'] = $catalog['galleryByService'];
            $data['frames'] = $this->showcaseFrames($salon);
        }
        if ($layout === 'editorial') {
            $data = array_merge($data, $this->editorial($salon));
        }
        if ($layout === 'app') {
            $data['nearest'] = $this->freeSlots->nearest($salon, 6);
            $data['galleryCover'] = $catalog['galleryFirst'] ? asset('storage/'.$catalog['galleryFirst']) : null;
        }

        return $data;
    }

    /**
     * قاب «نمایشگاه»: عکس‌های گالری به‌ترتیب، با نام خدمت و کارشناس اگر پیوند دارند.
     *
     * @return list<array{image: string, title: string, service_id: int|null, service: string|null, specialist_id: int|null, specialist: string|null}>
     */
    private function showcaseFrames(Salon $salon): array
    {
        return Cache::remember("home_showcase_gallery:{$salon->id}", 600, fn () => self::existingGallery(self::SHOWCASE_FRAMES)
            ->load(['service:id,name', 'specialist:id,name'])
            ->map(fn (GalleryImage $g) => [
                'image' => $g->image_url,
                'title' => (string) $g->title,
                'service_id' => $g->service ? (int) $g->service->id : null,
                'service' => $g->service?->name,
                'specialist_id' => $g->specialist ? (int) $g->specialist->id : null,
                'specialist' => $g->specialist?->name,
            ])->all());
    }

    /**
     * عکس‌های فعال گالری به‌ترتیب که فایلشان واقعاً روی دیسک هست (آزمون Chrome ۲۰۲۶-۱۱-۲۶: رکوردهای نمونه‌ی seed بی فایل،
     * قاب و بندانگشتی‌های شکسته می‌ساختند). تا ۲۰۰ رکورد اول بررسی می‌شود (آزمون دوم: ۸ رکورد بی فایل جلوی عکس واقعی
     * بودند و پنجره‌ی «۵ برابر» کاور جایگزین را پیدا نمی‌کرد)؛ نتیجه در cache خود صفحه‌ها می‌ماند.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, GalleryImage>
     */
    private static function existingGallery(int $limit): \Illuminate\Database\Eloquent\Collection
    {
        $disk = \Illuminate\Support\Facades\Storage::disk('public');

        return GalleryImage::query()->where('is_active', true)->orderBy('order')->orderBy('id')
            ->take(self::GALLERY_SCAN)->get(['id', 'title', 'image_path', 'service_id', 'specialist_id', 'order'])
            ->filter(fn (GalleryImage $g) => $g->image_path && $disk->exists($g->image_path))
            ->take($limit)->values();
    }

    /**
     * «روزنامه‌ی سالن»: پیشنهاد ویژه (فقط کد «نمایش در سایت»)، نقل از نظرهای تأییدشده، پکیج‌های فروشی و خط باشگاه مشتریان.
     *
     * @return array<string, mixed>
     */
    private function editorial(Salon $salon): array
    {
        $extra = Cache::remember("home_editorial:{$salon->id}", 600, function () use ($salon) {
            $offer = DiscountCode::query()->shownOnSite()->orderByDesc('id')->first();
            $quote = Review::query()->where('is_approved', true)
                ->whereNotNull('comment')->whereRaw("trim(comment) != ''")
                ->latest('id')->value('comment');
            $packages = $salon->hasFeature(Feature::TreatmentCourses)
                ? CoursePackage::query()->onSale()->take(3)->get()->map(fn (CoursePackage $p) => [
                    'id' => (int) $p->id,
                    'name' => (string) $p->name,
                    'sessions' => (int) $p->sessions_count,
                    'interval' => self::intervalText((int) $p->interval_days),
                    'price' => (float) $p->price,
                    'list' => $p->savingAmount() > 0 ? $p->listPrice() : null,
                ])->all()
                : [];
            $rewards = $salon->hasFeature(Feature::Loyalty)
                ? Reward::query()->where('is_active', true)->count()
                : 0;

            return [
                'offer' => $offer ? [
                    'code' => (string) $offer->code,
                    'title' => $offer->type === 'percentage'
                        ? Fa::percent((float) $offer->amount).' تخفیف'
                        : Fa::money((float) $offer->amount, true).' تخفیف',
                    'until' => $offer->expires_at ? Fa::jdate($offer->expires_at, 'j F') : null,
                ] : null,
                'quote' => $quote ? \Illuminate\Support\Str::limit(trim((string) $quote), 140) : null,
                'packages' => $packages,
                'rewards' => (int) $rewards,
            ];
        });

        $extra['nearest'] = $this->freeSlots->nearest($salon, 1)[0] ?? null;

        return $extra;
    }

    /** «هر هفته یک بار» / «هر ۲ هفته یک بار» / «هر ماه یک بار» / «هر ۱۰ روز یک بار»؛ بی فاصله ← null */
    public static function intervalText(int $days): ?string
    {
        return match (true) {
            $days <= 0 => null,
            $days === 7 => 'هر هفته یک بار',
            $days % 30 === 0 => ($days === 30 ? 'هر ماه' : 'هر '.Fa::number($days / 30).' ماه').' یک بار',
            $days % 7 === 0 => 'هر '.Fa::number($days / 7).' هفته یک بار',
            default => 'هر '.Fa::number($days).' روز یک بار',
        };
    }
}
