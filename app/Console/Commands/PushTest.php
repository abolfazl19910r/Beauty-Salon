<?php

namespace App\Console\Commands;

use App\Models\PushDelivery;
use App\Models\PushDevice;
use App\Models\User;
use App\Services\Push\AppMessenger;
use App\Services\Push\FcmClient;
use App\Services\Push\PushEnvironment;
use App\Services\Push\PushStats;
use Illuminate\Console\Command;
use Illuminate\Support\Sleep;

/**
 * پوش آزمایشی به همه‌ی گوشی‌هایی که با یک شماره وارد اپ شده‌اند (بسته‌ی ۴) — جای کیت آزمایشی بسته‌ی ۰ برای تست دستی.
 * نتیجه‌ی پذیرش FCM همین‌جا چاپ می‌شود؛ رسید را اپ چند ثانیه بعد می‌فرستد.
 *
 * عیب‌یابی «اعلان در سینی گوشی دیده نشد» (A17، ۲۰۲۶-۱۰-۱۹): --wait منتظر رسید گوشی می‌ماند و --style همین یک بار سبک پیام
 * FCM را عوض می‌کند (بی‌تغییر .env). با اپ بسته هر دو سبک را بفرستید:
 *   data رسید آمد ولی سینی خالی ← نمایش روی گوشی (اجازه/کانال اعلان)؛ data بی‌رسید ولی notification دیده شد ← گوشی پیام
 *   بی‌اعلان را به اپ نمی‌دهد (خواب عمیق سامسونگ) ← PUSH_FCM_STYLE=notification؛ هیچ‌کدام ← اجازه‌ی اعلان/باتری.
 *
 * سبز کردن کارت «آمادگی برای فعال» (تصمیم ۱-الف، ۲۰۲۶-۱۰-۲۲): --repeat=N --gap=ثانیه چند دور پوش با فاصله می‌فرستد و در آخر رسید
 * هر گوشی و وضعیت کارت هر اپ (و چند ارسال دیگر لازم است) را چاپ می‌کند. اپ را پیش از اجرا کامل ببندید.
 */
class PushTest extends Command
{
    protected $signature = 'push:test {phone : شماره‌ی موبایل کاربر اپ} {--text= : متن دلخواه}
        {--style= : سبک پیام FCM فقط برای همین ارسال: data یا notification (پیش‌فرض: PUSH_FCM_STYLE)}
        {--wait=0 : چند ثانیه بعد از آخرین دور منتظر رسید گوشی بماند (مثلاً 30)}
        {--repeat=1 : چند دور پوش بفرستد (حداکثر ۵۰)}
        {--gap=10 : فاصله‌ی دورها به ثانیه}';

    protected $description = 'پوش آزمایشی به گوشی‌های یک شماره و نمایش نتیجه‌ی FCM';

    public function handle(FcmClient $fcm, AppMessenger $messenger): int
    {
        foreach (PushEnvironment::checks(fallbackPath: false) as $check) {
            match ($check['level']) {
                'info' => $this->line($check['text']),
                default => $this->warn($check['text']),
            };
        }

        $style = $this->option('style');
        $defaultStyle = config('push.fcm.style');
        if ($style !== null) {
            if (! in_array($style, ['data', 'notification'], true)) {
                $this->error('--style فقط data یا notification است.');

                return self::FAILURE;
            }
            // ارسال این دستور هم‌زمان (sync) است؛ بعد از ارسال برمی‌گردد (پایین)
            config(['push.fcm.style' => $style]);
        }
        $this->line('سبک پیام FCM: '.config('push.fcm.style'));

        if (! $fcm->configured()) {
            $this->error('PUSH_FCM_CREDENTIALS در .env تنظیم نشده است (بعد php artisan config:clear).');

            return self::FAILURE;
        }

        $check = $fcm->check();
        if (! $check->ok) {
            $this->error('توکن دسترسی گوگل گرفته نشد: '.$check->error);

            return self::FAILURE;
        }
        $this->info('اتصال به گوگل و حساب سرویس: سالم');

        $phone = strtr(trim((string) $this->argument('phone')), ['۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9']);
        $userIds = User::query()->where('phone', $phone)->pluck('id');
        $devices = PushDevice::query()->active()->whereIn('user_id', $userIds)->get();

        if ($devices->isEmpty()) {
            $this->warn('هیچ گوشی فعالی با این شماره ثبت نشده است (در اپ وارد شوید و اجازه‌ی اعلان را بدهید).');

            return self::FAILURE;
        }

        $repeat = max(1, min(50, (int) $this->option('repeat')));
        $gap = max(0, (int) $this->option('gap'));

        $rows = [];
        for ($round = 1; $round <= $repeat; $round++) {
            foreach ($devices as $device) {
                $text = $this->option('text') ?: null;
                if ($text === null && $repeat > 1) {
                    $text = 'پوش آزمایشی '.to_persian_num("{$round} از {$repeat}").' — اگر این را می‌بینید، اعلان‌های نوبت به این گوشی می‌رسد.';
                }
                $message = $messenger->sendTest($device, $text, sync: true);
                $delivery = PushDelivery::query()->where('push_message_id', $message->id)->first();
                $rows[] = [
                    $round, $device->id, $device->app, trim(($device->manufacturer ?? '').' '.($device->model ?? '')),
                    $device->created_at?->format('Y-m-d H:i'), $delivery?->status, $delivery?->error ?? '—', $message->id,
                ];
            }

            if ($round < $repeat && $gap > 0) {
                Sleep::for($gap)->seconds();
            }
        }

        config(['push.fcm.style' => $defaultStyle]);

        $this->table(['دور', 'دستگاه', 'اپ', 'گوشی', 'ثبت (ورود)', 'نتیجه', 'خطا', 'شناسه‌ی پیام'], $rows);

        $messageIds = array_column($rows, 7);
        $wait = max(0, (int) $this->option('wait'));
        if ($wait > 0) {
            $this->waitForReceipts($messageIds, $wait, perMessage: $repeat === 1);
        }

        if ($repeat > 1 || $wait > 0) {
            $this->summarize($devices, $messageIds);
        }

        return collect($rows)->every(fn ($r) => $r[5] === PushDelivery::ACCEPTED) ? self::SUCCESS : self::FAILURE;
    }

    /**
     * رسید هر گوشی در همین اجرا، و وضعیت کارت «آمادگی برای فعال» هر اپ با تعداد ارسال تازه‌ی لازم.
     *
     * @param  \Illuminate\Support\Collection<int, PushDevice>  $devices
     * @param  array<int, string>  $messageIds
     */
    private function summarize($devices, array $messageIds): void
    {
        $this->newLine();
        $this->line('رسید در همین اجرا:');
        foreach ($devices as $device) {
            $mine = PushDelivery::query()->whereIn('push_message_id', $messageIds)->where('push_device_id', $device->id);
            $sent = (clone $mine)->where('status', PushDelivery::ACCEPTED)->count();
            $acked = (clone $mine)->whereNotNull('acked_at')->count();
            $line = "  دستگاه {$device->id} ({$device->app}، ".trim(($device->manufacturer ?? '').' '.($device->model ?? '')).'): '
                .to_persian_num("{$acked} از {$sent}");
            $acked === $sent ? $this->info($line) : $this->warn($line);
        }

        $readiness = app(PushStats::class)->readiness();
        $this->newLine();
        $this->line('کارت «آمادگی برای فعال» ('.to_persian_num((string) $readiness['days']).' روز اخیر): '.($readiness['ready'] ? 'آماده' : 'هنوز نه'));
        foreach ($readiness['checks'] as $check) {
            $line = '  '.($check['ok'] ? '✓' : ($check['blocking'] ? '✗' : '!')).' '.$check['label'].': '.$check['detail'];
            $check['ok'] ? $this->line($line) : $this->warn($line);
        }

        foreach ($readiness['apps'] as $app => $stats) {
            if ($stats['devices'] === 0 || $stats['needed'] === 0) {
                continue;
            }
            $this->line("  اپ {$app}: دست‌کم ".to_persian_num((string) $stats['needed']).' ارسال تازه‌ی دیگر با رسید لازم است'
                .($stats['stale_unacked'] > 0 ? ' (از ارسال‌های بی‌رسید این بازه، '.to_persian_num((string) $stats['stale_unacked']).' تا به گوشی‌های غیرفعال‌شده بوده)' : ''));
        }
    }

    /**
     * رسید را اپ می‌فرستد (باز: شنونده‌ی اعلان؛ بسته: task پس‌زمینه). رسید یعنی پیام به خود اپ رسید — نه لزوماً اینکه در سینی
     * دیده شد؛ «باز شد» یعنی روی اعلان زده شد.
     *
     * @param  array<int, string>  $messageIds
     */
    private function waitForReceipts(array $messageIds, int $seconds, bool $perMessage = true): void
    {
        $this->line("منتظر رسید گوشی (تا {$seconds} ثانیه)…");

        $acked = [];
        for ($elapsed = 0; $elapsed <= $seconds; $elapsed++) {
            $acked = PushDelivery::query()->whereIn('push_message_id', $messageIds)->whereNotNull('acked_at')->pluck('push_message_id')->all();
            if (count($acked) === count($messageIds) || $elapsed === $seconds) {
                break;
            }
            Sleep::for(1)->second();
        }

        if (! $perMessage) {
            return;
        }

        foreach ($messageIds as $id) {
            if (in_array($id, $acked, true)) {
                $this->info("رسید آمد: {$id} — پیام به اپ رسید؛ اگر در سینی نیست، اجازه یا کانال اعلان گوشی را ببینید.");
            } else {
                $this->warn("رسیدی نیامد: {$id} — گوشی پیام را به اپ نداد (خواب عمیق/باتری، اینترنت) یا اپ رسید را نفرستاد؛ با --style=notification هم امتحان کنید.");
            }
        }
    }
}
