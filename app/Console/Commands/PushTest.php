<?php

namespace App\Console\Commands;

use App\Models\PushDelivery;
use App\Models\PushDevice;
use App\Models\User;
use App\Services\Push\AppMessenger;
use App\Services\Push\FcmClient;
use App\Services\Push\PushMode;
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
 */
class PushTest extends Command
{
    protected $signature = 'push:test {phone : شماره‌ی موبایل کاربر اپ} {--text= : متن دلخواه}
        {--style= : سبک پیام FCM فقط برای همین ارسال: data یا notification (پیش‌فرض: PUSH_FCM_STYLE)}
        {--wait=0 : چند ثانیه منتظر رسید گوشی بماند (مثلاً 30)}';

    protected $description = 'پوش آزمایشی به گوشی‌های یک شماره و نمایش نتیجه‌ی FCM';

    public function handle(FcmClient $fcm, AppMessenger $messenger): int
    {
        $this->line('حالت پوش پلتفرم: '.PushMode::label(PushMode::current()));

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

        $rows = [];
        foreach ($devices as $device) {
            $message = $messenger->sendTest($device, $this->option('text') ?: null, sync: true);
            $delivery = PushDelivery::query()->where('push_message_id', $message->id)->first();
            $rows[] = [
                $device->id, $device->app, trim(($device->manufacturer ?? '').' '.($device->model ?? '')),
                $device->created_at?->format('Y-m-d H:i'), $delivery?->status, $delivery?->error ?? '—', $message->id,
            ];
        }

        config(['push.fcm.style' => $defaultStyle]);

        $this->table(['دستگاه', 'اپ', 'گوشی', 'ثبت (ورود)', 'نتیجه', 'خطا', 'شناسه‌ی پیام'], $rows);

        $wait = max(0, (int) $this->option('wait'));
        if ($wait > 0) {
            $this->waitForReceipts(array_column($rows, 6), $wait);
        }

        return collect($rows)->every(fn ($r) => $r[4] === PushDelivery::ACCEPTED) ? self::SUCCESS : self::FAILURE;
    }

    /**
     * رسید را اپ می‌فرستد (باز: شنونده‌ی اعلان؛ بسته: task پس‌زمینه). رسید یعنی پیام به خود اپ رسید — نه لزوماً اینکه در سینی
     * دیده شد؛ «باز شد» یعنی روی اعلان زده شد.
     *
     * @param  array<int, string>  $messageIds
     */
    private function waitForReceipts(array $messageIds, int $seconds): void
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

        foreach ($messageIds as $id) {
            if (in_array($id, $acked, true)) {
                $this->info("رسید آمد: {$id} — پیام به اپ رسید؛ اگر در سینی نیست، اجازه یا کانال اعلان گوشی را ببینید.");
            } else {
                $this->warn("رسیدی نیامد: {$id} — گوشی پیام را به اپ نداد (خواب عمیق/باتری، اینترنت) یا اپ رسید را نفرستاد؛ با --style=notification هم امتحان کنید.");
            }
        }
    }
}
