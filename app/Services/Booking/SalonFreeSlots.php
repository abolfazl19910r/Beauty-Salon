<?php

namespace App\Services\Booking;

use App\Models\BeautyService;
use App\Models\Salon;
use App\Models\Specialist;
use App\Support\Fa;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * ⭐ بسته‌ی ۵الف (۲۰۲۶-۱۱-۲۵): ساعت‌های خالی برای صفحه‌ی اول قالب‌های پولی (نکته‌ی ۵ بوم «۴ قالب ترکیبی») — فرم رزرو «شاهکار»
 * (نوار روزها و ساعت‌ها)، نوار «نوبت‌های خالی» قالب «اپ» و «نوبت خالی بعدی» هر کارشناس.
 *
 * فقط روی همان Specialist::getAvailableSlots() (همان مرخصی، تعطیلی، استراحت و نوبت‌های موجود که فرم رزرو می‌بیند)؛ محدود به
 * چند روز آینده (LOOKAHEAD_DAYS) و کش کوتاه (CACHE_SECONDS، کلید با salon_id) تا صفحه‌ی اول کند نشود. هر خطا ← خروجی خالی
 * (فراخوان آن تکه را پنهان می‌کند). سالن با رزرو متوقف‌شده هیچ ساعتی نشان نمی‌دهد. قطعی‌شدن نوبت همچنان با مسیر رزرو است.
 */
final class SalonFreeSlots
{
    public const CACHE_SECONDS = 120;

    public const LOOKAHEAD_DAYS = 7;

    /** مدت پیش‌فرض وقتی خدمتی انتخاب نشده (همان پیش‌فرض getAvailableSlots) */
    private const GENERIC_MINUTES = 30;

    /**
     * روزهای کاری پیش رو برای یک خدمت (و اختیاری یک کارشناس) با ساعت‌های خالی هر روز؛ «فرقی نمی‌کند» = هر ساعت از اولین
     * کارشناسی که آن را خالی دارد. روزی که هیچ کارشناسِ این خدمت برنامه‌ی کاری ندارد (مثلاً جمعه) رد می‌شود.
     *
     * @return list<array{date: string, label: string, day: string, slots: list<array{time: string, label: string, specialist_id: int}>}>
     */
    public function daysForService(Salon $salon, BeautyService $service, ?int $specialistId = null, int $days = 5): array
    {
        if ($salon->isBookingPaused()) {
            return [];
        }
        $key = sprintf('free_slots:days:%d:%d:%s:%d:%s', $salon->id, $service->id, $specialistId ?? 'any', $days, self::bucket());

        return $this->remember($key, function () use ($service, $specialistId, $days) {
            $specialists = $this->specialistsFor($service, $specialistId);
            if ($specialists->isEmpty()) {
                return [];
            }
            $duration = (int) ($service->duration ?: self::GENERIC_MINUTES);
            $out = [];
            foreach ($this->upcomingDates() as $date) {
                if (count($out) >= $days) {
                    break;
                }
                $working = $specialists->filter(fn (Specialist $s) => $s->schedules->contains(fn ($row) => (int) $row->day_of_week === $date->dayOfWeek));
                if ($working->isEmpty()) {
                    continue;
                }
                $slots = [];
                foreach ($working as $specialist) {
                    foreach ($specialist->getAvailableSlots($date->toDateString(), $duration) as $time) {
                        $slots[$time] ??= ['time' => $time, 'label' => Fa::digits($time), 'specialist_id' => (int) $specialist->id];
                    }
                }
                ksort($slots);
                $out[] = ['date' => $date->toDateString(), 'label' => $this->dayLabel($date), 'day' => Fa::jdate($date, 'j'), 'slots' => array_values($slots)];
            }

            return $out;
        });
    }

    /**
     * نزدیک‌ترین ساعت‌های خالی همه‌ی کارشناسان (بی خدمت مشخص، مدت پیش‌فرض)؛ برای نوار «نوبت‌های خالی» قالب «اپ».
     *
     * @return list<array{date: string, time: string, day_label: string, label: string, specialist_id: int}>
     */
    public function nearest(Salon $salon, int $limit = 6): array
    {
        if ($salon->isBookingPaused()) {
            return [];
        }

        return $this->remember(sprintf('free_slots:nearest:%d:%d:%s', $salon->id, $limit, self::bucket()), function () use ($limit) {
            $specialists = Specialist::query()->with(['schedules' => fn ($q) => $q->where('is_active', true)])->get();
            $out = [];
            foreach ($this->upcomingDates() as $date) {
                $day = [];
                foreach ($specialists as $specialist) {
                    if (! $specialist->schedules->contains(fn ($row) => (int) $row->day_of_week === $date->dayOfWeek)) {
                        continue;
                    }
                    foreach ($specialist->getAvailableSlots($date->toDateString(), self::GENERIC_MINUTES) as $time) {
                        $day[$time] ??= (int) $specialist->id;
                    }
                }
                ksort($day);
                foreach ($day as $time => $specialistId) {
                    $out[] = ['date' => $date->toDateString(), 'time' => $time, 'day_label' => $this->dayLabel($date), 'label' => Fa::digits($time), 'specialist_id' => $specialistId];
                    if (count($out) >= $limit) {
                        return $out;
                    }
                }
            }

            return $out;
        });
    }

    /**
     * نوبت خالی بعدی هر کارشناس («نوبت خالی: امروز ۱۹:۰۰»)؛ کارشناس بی نوبت خالی در LOOKAHEAD_DAYS کلیدی ندارد.
     *
     * @param  Collection<int, Specialist>  $specialists
     * @return array<int, array{date: string, time: string, text: string}>
     */
    public function nextForSpecialists(Salon $salon, Collection $specialists): array
    {
        if ($salon->isBookingPaused() || $specialists->isEmpty()) {
            return [];
        }
        $ids = $specialists->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all();

        return $this->remember(sprintf('free_slots:next:%d:%s:%s', $salon->id, md5(implode(',', $ids)), self::bucket()), function () use ($ids) {
            $out = [];
            $specialists = Specialist::query()->whereIn('id', $ids)->with(['schedules' => fn ($q) => $q->where('is_active', true)])->get();
            foreach ($specialists as $specialist) {
                foreach ($this->upcomingDates() as $date) {
                    if (! $specialist->schedules->contains(fn ($row) => (int) $row->day_of_week === $date->dayOfWeek)) {
                        continue;
                    }
                    $slots = $specialist->getAvailableSlots($date->toDateString(), self::GENERIC_MINUTES);
                    if ($slots !== []) {
                        $out[(int) $specialist->id] = ['date' => $date->toDateString(), 'time' => $slots[0], 'text' => $this->dayLabel($date).' '.Fa::digits($slots[0])];
                        break;
                    }
                }
            }

            return $out;
        });
    }

    /** «امروز» / «فردا» / نام روز هفته */
    public function dayLabel(Carbon $date): string
    {
        if ($date->isToday()) {
            return 'امروز';
        }
        if ($date->isTomorrow()) {
            return 'فردا';
        }

        return Fa::jdate($date, 'l');
    }

    /** @return Collection<int, Specialist> */
    private function specialistsFor(BeautyService $service, ?int $specialistId): Collection
    {
        return $service->specialists()
            ->when($specialistId, fn ($q) => $q->where('specialists.id', $specialistId))
            ->with(['schedules' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('specialists.id')
            ->get();
    }

    /** @return list<Carbon> */
    private function upcomingDates(): array
    {
        $dates = [];
        for ($i = 0; $i < self::LOOKAHEAD_DAYS; $i++) {
            $dates[] = Carbon::today()->addDays($i);
        }

        return $dates;
    }

    /** بازه‌ی زمانی کلید کش: ساعتِ گذشته‌ی امروز حداکثر CACHE_SECONDS در فهرست می‌ماند */
    private static function bucket(): string
    {
        return (string) intdiv(now()->getTimestamp(), self::CACHE_SECONDS);
    }

    /**
     * عمر کش CACHE_SECONDS (کلید با بازه‌ی bucket()).
     *
     * @template T
     *
     * @param  \Closure(): T  $build
     * @return T|array{}
     */
    private function remember(string $key, \Closure $build): mixed
    {
        try {
            return Cache::remember($key, self::CACHE_SECONDS, $build);
        } catch (\Throwable $e) {
            Log::warning('SalonFreeSlots: '.$e->getMessage(), ['key' => $key]);

            return [];
        }
    }
}
