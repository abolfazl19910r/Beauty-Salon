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

    /** آمادگی برای «فعال» (تصمیم ۶-۱ ب، ۲۰۲۶-۱۰-۲۲): حداقل ارسال و نرخ رسید هر اپ در این بازه */
    public const READY_MIN_DELIVERIES = 5;

    public const READY_MIN_ACK_RATE = 80.0;

    /**
     * کارت «آمادگی برای فعال» صفحه‌ی پوش و پنجره‌ی تأیید رفتن به «فعال». ارسال‌های پوش آزمایشی (push:test و پنل) هم حساب می‌شوند،
     * چون پیش از راه‌اندازی سرور بیشتر سنجش با همین‌هاست. شرط‌های مسدودکننده: کلید FCM، سبک data (با notification رسید فقط با
     * باز کردن اپ می‌آید و در «فعال» تقریباً همه‌چیز پیامک می‌شد)، و برای هر اپی که گوشی دارد حداقل ارسال و نرخ رسید.
     *
     * @return array{ready: bool, days: int, checks: list<array{ok: bool, blocking: bool, label: string, detail: string}>, apps: array<string, array>}
     */
    public function readiness(int $days = 7): array
    {
        $since = now()->subDays($days);
        $fcmConfigured = app(FcmClient::class)->configured();
        $style = (string) config('push.fcm.style');
        $fa = fn ($n) => to_persian_num((string) $n);

        $checks = [
            ['ok' => $fcmConfigured, 'blocking' => true, 'label' => 'کلید حساب سرویس فایربیس روی سرور',
                'detail' => $fcmConfigured ? 'تنظیم شده' : 'PUSH_FCM_CREDENTIALS خالی است'],
            ['ok' => $style === 'data', 'blocking' => true, 'label' => 'سبک ارسال data (رسید از اپ بسته)',
                'detail' => $style === 'data' ? 'data' : "{$style} — رسید فقط با باز کردن اپ می‌آید؛ در «فعال» تقریباً همه‌ی پیام‌ها پیامک می‌شوند"],
        ];

        $apps = [];
        foreach ([PushDevice::APP_CUSTOMER, PushDevice::APP_STAFF] as $app) {
            $devices = PushDevice::query()->active()->where('app', $app)->get();
            $deliveries = PushDelivery::query()
                ->join('push_devices', 'push_devices.id', '=', 'push_deliveries.push_device_id')
                ->where('push_devices.app', $app)
                ->where('push_deliveries.created_at', '>=', $since)
                ->where('push_deliveries.status', PushDelivery::ACCEPTED)
                ->selectRaw('count(*) as c, sum(case when push_deliveries.acked_at is not null then 1 else 0 end) as a')
                ->first();
            $sent = (int) ($deliveries->c ?? 0);
            $acked = (int) ($deliveries->a ?? 0);
            $rate = $sent > 0 ? round($acked * 100 / $sent, 1) : null;
            $unhealthy = $devices->take(500)->filter(fn (PushDevice $d) => AppMessenger::isUnhealthy($d))->count();

            // ارسال‌های بی‌رسید این بازه به گوشی‌هایی که دیگر فعال نیستند (اپ حذف، داده پاک یا خروج): نرخ را پایین می‌کشند ولی با
            // ارسال تازه رسید نمی‌گیرند — فقط برای توضیح در push:test (کارت همان قاعده‌ی تصمیم ۶-۱ ب را دارد)
            $staleUnacked = $sent > $acked ? PushDelivery::query()
                ->join('push_devices', 'push_devices.id', '=', 'push_deliveries.push_device_id')
                ->where('push_devices.app', $app)
                ->where('push_deliveries.created_at', '>=', $since)
                ->where('push_deliveries.status', PushDelivery::ACCEPTED)
                ->whereNull('push_deliveries.acked_at')
                ->whereNotIn('push_devices.id', $devices->pluck('id'))
                ->count() : 0;

            $apps[$app] = [
                'devices' => $devices->count(),
                'stale_unacked' => $staleUnacked,
                'needed' => self::sendsNeeded($sent, $acked),
                'never_acked' => $devices->whereNull('last_receipt_at')->count(),
                'unhealthy' => $unhealthy,
                'sent' => $sent,
                'acked' => $acked,
                'ack_rate' => $rate,
            ];

            $name = $app === PushDevice::APP_STAFF ? 'ماهرو همکار' : 'ماهرو (مشتری)';
            if ($devices->isEmpty()) {
                $checks[] = ['ok' => true, 'blocking' => false, 'label' => "اپ «{$name}»", 'detail' => 'هنوز گوشی ثبت‌شده‌ای ندارد؛ این کاربران مثل قبل پیامک می‌گیرند'];

                continue;
            }

            $ok = $sent >= self::READY_MIN_DELIVERIES && $rate !== null && $rate >= self::READY_MIN_ACK_RATE;
            $checks[] = ['ok' => $ok, 'blocking' => true, 'label' => "نرخ رسید «{$name}» در {$fa($days)} روز",
                'detail' => $sent === 0 ? 'هنوز پوشی نرفته'
                    : $fa($acked).' از '.$fa($sent).' ('.$fa($rate).'٪)'.($sent < self::READY_MIN_DELIVERIES ? ' — دست‌کم '.$fa(self::READY_MIN_DELIVERIES).' ارسال لازم است' : '')
                        .($rate !== null && $rate < self::READY_MIN_ACK_RATE ? ' — کمتر از '.$fa((int) self::READY_MIN_ACK_RATE).'٪' : '')];

            if ($unhealthy > 0) {
                $checks[] = ['ok' => false, 'blocking' => false, 'label' => "گوشی‌های ناسالم «{$name}»",
                    'detail' => $fa($unhealthy).' گوشی دو پوش آخرش بی‌رسید مانده؛ پیام فوری به آن‌ها مستقیم پیامک می‌شود (Autostart / Deep sleeping)'];
            }
        }

        return [
            'ready' => collect($checks)->every(fn ($c) => $c['ok'] || ! $c['blocking']),
            'days' => $days,
            'checks' => $checks,
            'apps' => $apps,
        ];
    }

    /**
     * چند ارسال تازه‌ی با رسید لازم است تا اپ از شرط کارت بگذرد: هم دست‌کم READY_MIN_DELIVERIES ارسال، هم نرخ ≥ READY_MIN_ACK_RATE.
     * (a + x) / (s + x) ≥ r  ⇒  x ≥ (r·s − a) / (1 − r). ارسال‌های قدیمی بازه‌ی ۷ روزه کم‌کم بیرون می‌روند و عدد واقعی کمتر می‌شود.
     */
    public static function sendsNeeded(int $sent, int $acked): int
    {
        $r = self::READY_MIN_ACK_RATE / 100;
        $forRate = $sent === 0 ? 0 : (int) max(0, ceil(round(($r * $sent - $acked) / (1 - $r), 6)));

        return max($forRate, self::READY_MIN_DELIVERIES - $sent);
    }

    /** متن کوتاه پنجره‌ی تأیید «فعال» در تنظیمات پلتفرم */
    public function readinessSummary(int $days = 7): string
    {
        $r = $this->readiness($days);
        $lines = collect($r['checks'])->map(fn ($c) => ($c['ok'] ? '✓ ' : ($c['blocking'] ? '✗ ' : '! ')).$c['label'].': '.$c['detail']);

        return ($r['ready'] ? 'آماده برای «فعال».' : 'هنوز آماده نیست.')."\n".$lines->implode("\n")
            ."\n\nدر «فعال» پیامک فقط وقتی می‌رود که اپ در مهلت رسید نفرستد. ادامه می‌دهید؟";
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
