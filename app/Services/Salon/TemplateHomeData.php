<?php

namespace App\Services\Salon;

use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\Category;
use App\Models\GalleryImage;
use App\Models\Salon;
use App\Models\Specialist;
use App\Services\Booking\SalonFreeSlots;
use App\Support\SalonWorkingHours;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * ⭐ بسته‌ی ۵الف/۵ب: داده‌ی صفحه‌ی خانه‌ی قالب‌های «شاهکار»، «اپ»، «نمایشگاه» و «روزنامه‌ی سالن» (قاعده‌ی ۴ بوم: فقط داده‌ی واقعی؛ نبود هر تکه ← آن تکه پنهان).
 * فهرست خدمات و کارشناسان صفحه از HomeController می‌آید؛ اینجا فقط چیزهایی که این دو قالب بیشتر می‌خواهند.
 */
final class TemplateHomeData
{
    /** قاب «نمایشگاه» حداکثر این تعداد نمونه‌کار را ورق می‌زند. */
    public const SHOWCASE_FRAMES = 8;

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
            'galleryFirst' => GalleryImage::query()->where('is_active', true)->orderBy('order')->orderBy('id')->value('image_path'),
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
        if ($layout === 'app') {
            $data['nearest'] = $this->freeSlots->nearest($salon, 6);
            $data['galleryCover'] = $catalog['gallery'] > 0
                ? GalleryImage::query()->where('is_active', true)->orderBy('order')->first()?->image_url
                : null;
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
        return Cache::remember("home_showcase_gallery:{$salon->id}", 600, fn () => GalleryImage::query()
            ->where('is_active', true)->orderBy('order')->orderBy('id')
            ->with(['service:id,name', 'specialist:id,name'])
            ->take(self::SHOWCASE_FRAMES)->get()
            ->map(fn (GalleryImage $g) => [
                'image' => $g->image_url,
                'title' => (string) $g->title,
                'service_id' => $g->service ? (int) $g->service->id : null,
                'service' => $g->service?->name,
                'specialist_id' => $g->specialist ? (int) $g->specialist->id : null,
                'specialist' => $g->specialist?->name,
            ])->all());
    }
}
