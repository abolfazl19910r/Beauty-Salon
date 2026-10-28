<?php

namespace App\Console\Commands;

use App\Models\PushDelivery;
use App\Models\PushDevice;
use App\Models\PushMessage;
use App\Models\User;
use App\Services\Push\AppMessenger;
use App\Services\Push\FcmClient;
use App\Services\Push\Outgoing;
use App\Services\Push\PushEnvironment;
use App\Services\Push\PushMode;
use App\Services\Push\PushPolicy;
use App\Services\Push\PushUrgency;
use Illuminate\Console\Command;
use Illuminate\Support\Sleep;

/**
 * آزمون رفتار حالت «فعال» (تصمیم ۲-الف، ۲۰۲۶-۱۰-۲۲): یک پیام واقعی با متن پیامک از همان مسیر رویدادها (AppMessenger::deliver)
 * به گوشی‌های یک شماره می‌فرستد و منتظر نتیجه می‌ماند. push:test این مسیر را نمی‌آزماید، چون پیامک ندارد.
 *
 * نتیجه‌ها: رسید آمد و پیامکی نرفت (صرفه‌جویی) / رسید تا مهلت نیامد و پیامک پشتیبان رفت / دستگاه ناسالم بود و پیام فوری مستقیم
 * پیامک شد / دسته‌ی info فقط پوش است. پیامک بدون سالن (هزینه‌ی پلتفرم) فرستاده می‌شود تا سهمیه‌ی سالنی کم نشود؛ روی لوکال با
 * KAVENEGAR_SEND_IN_LOCAL=false فقط در laravel.log ثبت می‌شود.
 *
 * پیش‌نیاز: حالت «فعال»، QUEUE_CONNECTION=database و queue:work روی صف‌های push و sms. برای آزمون کوتاه‌تر دسته‌ی normal موقتاً
 * PUSH_FALLBACK_NORMAL_SECONDS=60 (و config:clear؛ worker را هم دوباره راه بیندازید).
 */
class PushLiveCheck extends Command
{
    protected $signature = 'push:live-check {phone : شماره‌ی موبایل کاربر اپ}
        {--urgency=urgent : دسته‌ی پیام: urgent (مهلت ۱ دقیقه)، normal (۱۰ دقیقه) یا info (فقط پوش)}
        {--app= : فقط یک اپ: customer یا staff (پیش‌فرض: هر دو)}
        {--wait= : حداکثر انتظار به ثانیه (پیش‌فرض: مهلت دسته + ۳۰)}';

    protected $description = 'آزمون مسیر «بدون رسید ← پیامک» حالت فعال با یک پیام واقعی';

    /** اگر پوش این‌قدر بعد از صف شدن هنوز «در صف» بود، worker صف push کار نمی‌کند */
    private const PUSH_PICKUP_SECONDS = 20;

    public function handle(AppMessenger $messenger, FcmClient $fcm): int
    {
        $urgency = PushUrgency::tryFrom((string) $this->option('urgency'));
        if ($urgency === null) {
            $this->error('--urgency فقط urgent، normal یا info است.');

            return self::FAILURE;
        }

        $app = $this->option('app');
        if ($app !== null && ! in_array($app, [PushDevice::APP_CUSTOMER, PushDevice::APP_STAFF], true)) {
            $this->error('--app فقط customer یا staff است.');

            return self::FAILURE;
        }

        $blocked = false;
        // در «آزمایشی» پیام info هم پیامک می‌شود (رفتار پیش از پوش؛ AppMessenger::deliver) — پس هشدار «آزمایشی» برای همه‌ی دسته‌ها
        foreach (PushEnvironment::checks(fallbackPath: true) as $check) {
            match ($check['level']) {
                'info' => $this->line($check['text']),
                'warn' => $this->warn($check['text']),
                'error' => $this->error($check['text']),
            };
            $blocked = $blocked || $check['level'] === 'error';
        }
        if (PushMode::current() === PushMode::OFF) {
            $blocked = true;
        }
        if (! $fcm->configured()) {
            $this->error('PUSH_FCM_CREDENTIALS در .env تنظیم نشده است (بعد php artisan config:clear).');
            $blocked = true;
        }
        if ($blocked) {
            return self::FAILURE;
        }

        $phone = strtr(trim((string) $this->argument('phone')), ['۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9']);
        $userIds = PushDevice::query()->active()
            ->whereIn('user_id', User::query()->where('phone', $phone)->select('id'))
            ->when($app, fn ($q) => $q->where('app', $app))
            ->distinct()->pluck('user_id');
        $users = User::query()->whereIn('id', $userIds)->orderBy('id')->get();

        if ($users->isEmpty()) {
            $this->warn('هیچ گوشی فعالی با این شماره'.($app ? " در اپ {$app}" : '').' ثبت نشده است.');

            return self::FAILURE;
        }

        $window = $urgency->fallbackSeconds();
        $this->line('دسته: '.$urgency->value.match (true) {
            PushMode::current() === PushMode::SHADOW => ' — در «آزمایشی» پیامک همان لحظه هم می‌رود',
            $window !== null => ' — مهلت رسید '.to_persian_num((string) $window).' ثانیه',
            default => ' — در «فعال» فقط پوش، هرگز پیامک',
        });

        $start = now()->subSecond();
        $messageIds = [];
        foreach ($users as $user) {
            $messenger->deliver(new Outgoing(
                recipient: $user,
                phone: $phone,
                text: 'آزمون حالت فعال ماهرو ('.$urgency->value.') — '.to_persian_num(now()->format('H:i:s')),
                salonId: null,
                smsFeature: null,
                pushKey: PushPolicy::LIVE_CHECK,
                urgency: $urgency,
            ));

            $id = PushMessage::query()->where('user_id', $user->id)->where('event_key', PushPolicy::LIVE_CHECK)
                ->where('created_at', '>=', $start)->orderByDesc('id')->value('id');
            if ($id) {
                $messageIds[] = $id;
            }
        }

        if ($messageIds === []) {
            $this->warn('پیامی ساخته نشد (پوش این کاربر غیرفعال است).');

            return self::FAILURE;
        }

        $wait = $this->option('wait') !== null ? max(0, (int) $this->option('wait')) : ($window ?? 0) + 30;
        $this->line('منتظر نتیجه (تا '.to_persian_num((string) $wait).' ثانیه)…');

        $warnedPush = false;
        $warnedSms = false;
        for ($elapsed = 0; $elapsed <= $wait; $elapsed++) {
            $messages = PushMessage::query()->whereIn('id', $messageIds)->get();
            if ($messages->every(fn (PushMessage $m) => $this->settled($m, $urgency))) {
                break;
            }

            if (! $warnedPush && $elapsed >= self::PUSH_PICKUP_SECONDS
                && PushDelivery::query()->whereIn('push_message_id', $messageIds)->where('status', PushDelivery::QUEUED)->exists()) {
                $this->warn('پوش هنوز «در صف» است: worker صف push کار نمی‌کند (php artisan queue:work --queue=otp,push,sms,...).');
                $warnedPush = true;
            }

            if (! $warnedSms && $messages->contains(fn (PushMessage $m) => $m->fallback_due_at && ! $m->acked_at && ! $m->sms_reason
                && $m->fallback_due_at->lt(now()->subSeconds(15)))) {
                $this->warn('مهلت رسید گذشته ولی پیامک پشتیبان هنوز نرفته: worker صف sms کار نمی‌کند.');
                $warnedSms = true;
            }

            if ($elapsed < $wait) {
                Sleep::for(1)->second();
            }
        }

        $ok = true;
        foreach (PushMessage::query()->whereIn('id', $messageIds)->with('deliveries.device')->get() as $message) {
            [$good, $text] = $this->verdict($message, $urgency);
            $devices = $message->deliveries->map(fn ($d) => trim(($d->device?->manufacturer ?? '').' '.($d->device?->model ?? '')).' ('.$d->status.')')->implode('، ');
            $line = "اپ {$message->app} — {$devices}: {$text}";
            $good ? $this->info($line) : $this->warn($line);
            $ok = $ok && $good;
        }

        return $ok ? self::SUCCESS : self::FAILURE;
    }

    /** نتیجه‌ی پیام قطعی شده؟ */
    private function settled(PushMessage $m, PushUrgency $urgency): bool
    {
        if ($m->sms_reason !== null && $m->sms_reason !== 'shadow') {
            return true;
        }

        if ($m->mode === PushMode::SHADOW) {
            return $m->acked_at !== null && ($urgency === PushUrgency::Info || $m->sms_sent_at !== null);
        }

        return $m->acked_at !== null;
    }

    /** @return array{0: bool, 1: string} [نتیجه‌ی قطعی و درست؟، متن] */
    private function verdict(PushMessage $m, PushUrgency $urgency): array
    {
        $fa = fn ($n) => to_persian_num((string) $n);
        $ackDelay = $m->acked_at ? $fa((int) $m->acked_at->diffInSeconds($m->created_at, true)).' ثانیه' : null;

        return match (true) {
            $m->sms_reason === 'unhealthy' => [true, 'دستگاه ناسالم بود (دو پوش آخرش بی‌رسید) ← پیام فوری مستقیم پیامک شد'.($ackDelay ? "؛ رسید بعداً در {$ackDelay} آمد" : '')],
            $m->sms_reason === 'no_receipt' && config('queue.default') === 'sync' => [false, 'صف sync: پیامک پشتیبان همان لحظه رفت و مهلت آزموده نشد'],
            $m->sms_reason === 'no_receipt' => [true, 'رسید تا مهلت نیامد ← پیامک پشتیبان رفت (laravel.log)'.($ackDelay ? "؛ رسید دیر، در {$ackDelay} آمد" : '')],
            $m->sms_reason === 'sms_blocked' => [false, 'رسید نیامد و پیامک پشتیبان مسدود شد (پلن/سهمیه)'],
            $m->sms_reason === 'shadow' => [false, 'حالت «آزمایشی»: پیامک همان لحظه رفت'.($ackDelay ? "؛ رسید در {$ackDelay}" : '؛ رسیدی نیامد').' — مسیر «فعال» آزموده نشد'],
            $m->acked_at !== null && $urgency === PushUrgency::Info => [true, "رسید در {$ackDelay} آمد؛ دسته‌ی info فقط پوش است و پیامکی نمی‌رود"],
            $m->acked_at !== null => [true, "رسید در {$ackDelay} آمد ← پیامکی نرفت (پیامک صرفه‌جویی‌شده)"],
            $urgency === PushUrgency::Info => [false, 'رسیدی نیامد؛ دسته‌ی info پیامک ندارد (فقط در صندوق اپ می‌ماند)'],
            default => [false, 'تا پایان انتظار نه رسید آمد نه پیامک — worker صف sms یا --wait را ببینید'],
        };
    }
}
