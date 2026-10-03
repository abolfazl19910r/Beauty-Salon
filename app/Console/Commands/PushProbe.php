<?php

namespace App\Console\Commands;

use App\Experiments\PushProbe\FcmProbeSender;
use App\Experiments\PushProbe\NtfyProbeSender;
use App\Experiments\PushProbe\ProbeMessage;
use App\Experiments\PushProbe\ProbeResult;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * بسته‌ی ۰ اپلیکیشن — آزمایش پوش از سرور ایران (فقط برنچ experiment/push-probe).
 *
 *   php artisan push:probe check                         سنجش راه‌ها بدون ارسال
 *   php artisan push:probe fcm <توکن FCM> --label=...    مستقیم از سرور به گوگل
 *   php artisan push:probe fcm-proxy <توکن FCM>          از Worker کلودفلر
 *   php artisan push:probe ntfy <تاپیک>                  سرور ntfy خودمیزبان (بدون گوگل؛ برای قطعی اینترنت بین‌الملل)
 *
 * «موفق» در خروجی یعنی سرویس پیام را پذیرفت؛ رسیدن به گوشی را اپ آزمایشی و فرم نتیجه نشان می‌دهند.
 * راهنمای کامل: experiments/push-probe/README.md
 */
class PushProbe extends Command
{
    protected $signature = 'push:probe
        {via : check | fcm | fcm-proxy | ntfy}
        {target? : توکن FCM گوشی یا نام تاپیک ntfy (هر دو در اپ آزمایشی)}
        {--count=1 : تعداد پیام}
        {--interval=5 : فاصله‌ی بین پیام‌ها به ثانیه}
        {--label= : برچسب آزمایش در متن پیام، مثلاً A17-بسته}';

    protected $description = 'آزمایش پوش (بسته‌ی ۰ اپلیکیشن): ارسال پیام آزمایشی با FCM یا ntfy و سنجش دسترسی سرور';

    public function handle(FcmProbeSender $fcm, NtfyProbeSender $ntfy): int
    {
        $via = (string) $this->argument('via');

        try {
            return match ($via) {
                'check' => $this->check($fcm, $ntfy),
                'fcm', 'fcm-proxy', 'ntfy' => $this->sendAll($via, $fcm, $ntfy),
                default => $this->failWith("مسیر «{$via}» شناخته نشد؛ یکی از check، fcm، fcm-proxy، ntfy."),
            };
        } catch (RuntimeException $e) {
            return $this->failWith($e->getMessage());
        }
    }

    private function sendAll(string $via, FcmProbeSender $fcm, NtfyProbeSender $ntfy): int
    {
        $target = trim((string) $this->argument('target'));
        if ($target === '') {
            return $this->failWith($via === 'ntfy'
                ? 'نام تاپیک ntfy را بدهید (در اپ آزمایشی، بخش ntfy).'
                : 'توکن FCM گوشی را بدهید (در اپ آزمایشی، بخش توکن FCM).');
        }

        $count = max(1, min(50, (int) $this->option('count')));
        $interval = max(0, (int) $this->option('interval'));
        $label = trim((string) $this->option('label'));
        $runId = Str::lower(Str::random(4));
        $rows = [];
        $allOk = true;

        $this->line("مسیر: {$via} — مقصد: {$this->mask($target)} — {$count} پیام".($count > 1 ? " با فاصله‌ی {$interval} ثانیه" : ''));

        for ($seq = 1; $seq <= $count; $seq++) {
            if ($seq > 1 && $interval > 0) {
                sleep($interval);
            }

            $message = ProbeMessage::make($runId, $seq, $via, $label);
            $result = $via === 'ntfy'
                ? $ntfy->send($target, $message)
                : $fcm->send($target, $message, $via === 'fcm-proxy');

            $allOk = $allOk && $result->ok;
            $rows[] = [
                $message->id,
                $message->sentAt()->format('H:i:s'),
                $result->ok ? 'پذیرفته شد' : 'ناموفق',
                $result->status ?? '—',
                $result->ms,
                $result->detail,
            ];
            $this->log($message, $target, $result);
        }

        $this->table(['شناسه', 'ساعت ارسال', 'نتیجه', 'HTTP', 'میلی‌ثانیه', 'توضیح'], $rows);
        $this->line('ثبت شد در: '.config('push_probe.log'));

        return $allOk ? self::SUCCESS : self::FAILURE;
    }

    private function check(FcmProbeSender $fcm, NtfyProbeSender $ntfy): int
    {
        $rows = [];
        $rows[] = $this->reach('گوگل: oauth2.googleapis.com', (string) config('push_probe.fcm.oauth_url'));
        $rows[] = $this->reach('گوگل: fcm.googleapis.com', rtrim((string) config('push_probe.fcm.api_url'), '/').'/');

        if (config('push_probe.fcm.credentials')) {
            // خطای تنظیم (مثلاً فایل کلید پیدا نشد) همان ردیف را ناموفق می‌کند و بقیه‌ی سنجش‌ها ادامه پیدا می‌کنند
            $rows[] = $this->guardedRow('توکن دسترسی FCM — مستقیم', fn () => $fcm->fetchAccessToken(false));
            if (config('push_probe.fcm.proxy')) {
                $rows[] = $this->guardedRow('توکن دسترسی FCM — از Worker', fn () => $fcm->fetchAccessToken(true));
            }
        } else {
            $rows[] = ['توکن دسترسی FCM', 'رد شد', '—', '—', 'PUSH_PROBE_FCM_CREDENTIALS تنظیم نشده'];
        }

        if (config('push_probe.ntfy.server')) {
            $rows[] = $this->row('سرور ntfy: '.$ntfy->server(), $ntfy->health());
        } else {
            $rows[] = ['سرور ntfy', 'رد شد', '—', '—', 'PUSH_PROBE_NTFY_SERVER تنظیم نشده'];
        }

        $this->table(['مورد', 'نتیجه', 'HTTP', 'میلی‌ثانیه', 'توضیح'], $rows);
        $this->line('برای گوگل «در دسترس» یعنی سرور جوابی گرفت (هر کد HTTP)؛ مهم ردیف «توکن دسترسی» است.');

        return self::SUCCESS;
    }

    /** فقط «آیا جوابی می‌آید»: هر کد HTTP یعنی مسیر شبکه باز است. */
    private function reach(string $title, string $url): array
    {
        $started = hrtime(true);

        try {
            $response = Http::timeout((int) config('push_probe.timeout'))->get($url);
            $ms = (int) round((hrtime(true) - $started) / 1_000_000);

            // 403 از گوگل می‌تواند «منطقه‌ی تحریمی» باشد؛ متن پاسخ را نشان بده تا قضاوت ممکن باشد
            $detail = $response->status() >= 400 ? mb_strimwidth(preg_replace('/\s+/', ' ', strip_tags($response->body())), 0, 120, '…') : '';

            return [$title, 'در دسترس', $response->status(), $ms, $detail];
        } catch (ConnectionException $e) {
            $ms = (int) round((hrtime(true) - $started) / 1_000_000);

            return [$title, 'نرسید', '—', $ms, mb_strimwidth($e->getMessage(), 0, 160, '…')];
        }
    }

    private function guardedRow(string $title, callable $probe): array
    {
        try {
            return $this->row($title, $probe());
        } catch (RuntimeException $e) {
            return [$title, 'ناموفق', '—', '—', $e->getMessage()];
        }
    }

    private function row(string $title, ProbeResult $result): array
    {
        return [$title, $result->ok ? 'موفق' : 'ناموفق', $result->status ?? '—', $result->ms, $result->detail];
    }

    private function log(ProbeMessage $message, string $target, ProbeResult $result): void
    {
        $path = (string) config('push_probe.log');
        $isNew = ! is_file($path);
        $handle = @fopen($path, 'ab');
        if ($handle === false) {
            $this->warn("فایل ثبت باز نشد: {$path}");

            return;
        }

        if ($isNew) {
            fputcsv($handle, ['sent_at', 'probe_id', 'via', 'label', 'target', 'accepted', 'http_status', 'ms', 'detail']);
        }

        fputcsv($handle, [
            $message->sentAt()->format('Y-m-d H:i:s'),
            $message->id,
            $message->via,
            $message->label,
            $this->mask($target),
            $result->ok ? '1' : '0',
            $result->status ?? '',
            $result->ms,
            $result->detail,
        ]);
        fclose($handle);
    }

    private function mask(string $target): string
    {
        return mb_strlen($target) > 16 ? mb_substr($target, 0, 8).'…'.mb_substr($target, -4) : $target;
    }

    private function failWith(string $message): int
    {
        $this->error($message);

        return self::FAILURE;
    }
}
