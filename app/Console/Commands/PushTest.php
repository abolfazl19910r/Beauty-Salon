<?php

namespace App\Console\Commands;

use App\Models\PushDelivery;
use App\Models\PushDevice;
use App\Models\User;
use App\Services\Push\AppMessenger;
use App\Services\Push\FcmClient;
use App\Services\Push\PushMode;
use Illuminate\Console\Command;

/**
 * پوش آزمایشی به همه‌ی گوشی‌هایی که با یک شماره وارد اپ شده‌اند (بسته‌ی ۴) — جای کیت آزمایشی بسته‌ی ۰ برای تست دستی.
 * نتیجه‌ی پذیرش FCM همین‌جا چاپ می‌شود؛ رسید را اپ چند ثانیه بعد می‌فرستد.
 */
class PushTest extends Command
{
    protected $signature = 'push:test {phone : شماره‌ی موبایل کاربر اپ} {--text= : متن دلخواه}';

    protected $description = 'پوش آزمایشی به گوشی‌های یک شماره و نمایش نتیجه‌ی FCM';

    public function handle(FcmClient $fcm, AppMessenger $messenger): int
    {
        $this->line('حالت پوش پلتفرم: '.PushMode::label(PushMode::current()));

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
            $rows[] = [$device->id, $device->app, trim(($device->manufacturer ?? '').' '.($device->model ?? '')), $delivery?->status, $delivery?->error ?? '—', $message->id];
        }

        $this->table(['دستگاه', 'اپ', 'گوشی', 'نتیجه', 'خطا', 'شناسه‌ی پیام'], $rows);

        return collect($rows)->every(fn ($r) => $r[3] === PushDelivery::ACCEPTED) ? self::SUCCESS : self::FAILURE;
    }
}
