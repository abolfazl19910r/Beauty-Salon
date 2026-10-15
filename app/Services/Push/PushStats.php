<?php

namespace App\Services\Push;

use App\Models\PushDelivery;
use App\Models\PushDevice;
use App\Models\PushMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * آمار صفحه‌ی «اعلان اپ (پوش)» ابرمدیر (بسته‌ی ۴): نرخ رسید و تأخیر در دوره‌ی آزمایشی، پیامک رفته و صرفه‌جویی‌شده، دستگاه‌ها.
 * پوش آزمایشی (push.test) در آمار پیام‌ها حساب نمی‌شود.
 */
class PushStats
{
    /** حداکثر ردیف برای میانه/صدک (بیش از این نمونه‌ی تازه‌ترین‌ها) */
    private const SAMPLE = 5000;

    public function summary(int $days): array
    {
        $since = now()->subDays($days);
        $messages = PushMessage::query()->where('created_at', '>=', $since)->where('event_key', '!=', PushPolicy::TEST);

        $total = (clone $messages)->count();
        $acked = (clone $messages)->whereNotNull('acked_at')->count();
        $delays = $this->delays((clone $messages)->whereNotNull('acked_at'));

        $smsByReason = (clone $messages)->whereNotNull('sms_sent_at')
            ->selectRaw('sms_reason, count(*) as c, sum(sms_parts) as parts')->groupBy('sms_reason')->get()
            ->mapWithKeys(fn ($r) => [(string) $r->sms_reason => ['count' => (int) $r->c, 'parts' => (int) $r->parts]])->all();

        // حالت فعال: پیامکی که لازم نشد (رسید آمد یا فقط‌پوش) — قطعه‌های صرفه‌جویی‌شده از سهمیه‌ی سالن‌ها
        $saved = (clone $messages)->where('mode', PushMode::LIVE)->whereNotNull('sms_text')->whereNull('sms_sent_at')->whereNull('sms_reason');

        return [
            'days' => $days,
            'total' => $total,
            'acked' => $acked,
            'ack_rate' => $total > 0 ? round($acked * 100 / $total, 1) : null,
            'median_seconds' => $this->percentile($delays, 50),
            'p90_seconds' => $this->percentile($delays, 90),
            'sms' => $smsByReason,
            'saved_count' => (clone $saved)->count(),
            'saved_parts' => (int) (clone $saved)->sum('sms_parts'),
            'shadow_would_save' => $this->shadowWouldSave($since),
            'by_event' => $this->byEvent($since),
            'by_maker' => $this->byMaker($since),
        ];
    }

    public function devices(): array
    {
        return [
            'active' => PushDevice::query()->active()->selectRaw('app, count(*) as c')->groupBy('app')->pluck('c', 'app')->map(fn ($c) => (int) $c)->all(),
            'disabled' => PushDevice::query()->whereNotNull('disabled_at')->selectRaw('disabled_reason, count(*) as c')->groupBy('disabled_reason')->pluck('c', 'disabled_reason')->map(fn ($c) => (int) $c)->all(),
            'never_acked' => PushDevice::query()->active()->whereNull('last_receipt_at')->count(),
        ];
    }

    /**
     * دوره‌ی آزمایشی: اگر حالت «فعال» بود، چند پیامک لازم نمی‌شد — رسید در مهلت دسته‌ی خودش آمده یا دسته‌ی فقط‌پوش.
     *
     * @return array{count: int, parts: int}
     */
    private function shadowWouldSave(Carbon $since): array
    {
        $count = 0;
        $parts = 0;

        PushMessage::query()->where('created_at', '>=', $since)->where('mode', PushMode::SHADOW)
            ->where('event_key', '!=', PushPolicy::TEST)->whereNotNull('sms_text')
            ->select(['id', 'urgency', 'created_at', 'acked_at', 'sms_parts'])
            ->orderByDesc('created_at')->limit(self::SAMPLE)
            ->get()
            ->each(function (PushMessage $m) use (&$count, &$parts) {
                $window = PushUrgency::tryFrom($m->urgency)?->fallbackSeconds();
                $inTime = $window === null || ($m->acked_at && $m->acked_at->diffInSeconds($m->created_at, true) <= $window);
                if ($inTime) {
                    $count++;
                    $parts += (int) $m->sms_parts;
                }
            });

        return ['count' => $count, 'parts' => $parts];
    }

    private function byEvent(Carbon $since): Collection
    {
        return PushMessage::query()->where('created_at', '>=', $since)->where('event_key', '!=', PushPolicy::TEST)
            ->selectRaw('event_key, urgency, count(*) as c, sum(case when acked_at is not null then 1 else 0 end) as a, sum(case when sms_sent_at is not null then 1 else 0 end) as s')
            ->groupBy('event_key', 'urgency')->orderByDesc('c')->get()
            ->map(fn ($r) => [
                'event' => $r->event_key,
                'title' => PushPolicy::title((string) $r->event_key),
                'urgency' => $r->urgency,
                'count' => (int) $r->c,
                'ack_rate' => $r->c > 0 ? round($r->a * 100 / $r->c, 1) : null,
                'sms' => (int) $r->s,
            ]);
    }

    /** نرخ رسید به تفکیک سازنده‌ی گوشی (شیائومی/سامسونگ؛ یافته‌های بسته‌ی ۰: Autostart و Deep sleeping) */
    private function byMaker(Carbon $since): Collection
    {
        return PushDelivery::query()
            ->join('push_devices', 'push_devices.id', '=', 'push_deliveries.push_device_id')
            ->join('push_messages', 'push_messages.id', '=', 'push_deliveries.push_message_id')
            ->where('push_messages.created_at', '>=', $since)
            ->where('push_messages.event_key', '!=', PushPolicy::TEST)
            ->selectRaw("coalesce(push_devices.manufacturer, '—') as maker, count(*) as c, sum(case when push_deliveries.acked_at is not null then 1 else 0 end) as a, sum(case when push_deliveries.status = 'accepted' then 1 else 0 end) as acc")
            ->groupBy('maker')->orderByDesc('c')->limit(10)->get()
            ->map(fn ($r) => [
                'maker' => $r->maker,
                'count' => (int) $r->c,
                'accepted' => (int) $r->acc,
                'ack_rate' => $r->c > 0 ? round($r->a * 100 / $r->c, 1) : null,
            ]);
    }

    /** @return list<int> تأخیر رسید (ثانیه) از ساخت پیام */
    private function delays($query): array
    {
        return $query->orderByDesc('created_at')->limit(self::SAMPLE)->get(['created_at', 'acked_at'])
            ->map(fn ($m) => (int) $m->acked_at->diffInSeconds($m->created_at, true))
            ->sort()->values()->all();
    }

    private function percentile(array $sorted, int $p): ?int
    {
        if ($sorted === []) {
            return null;
        }

        return $sorted[(int) min(count($sorted) - 1, floor(($p / 100) * (count($sorted) - 1)))];
    }
}
