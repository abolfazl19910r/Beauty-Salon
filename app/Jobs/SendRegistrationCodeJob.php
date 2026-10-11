<?php

namespace App\Jobs;

use App\Services\SMSService;
use App\Support\Queues;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * کد تأیید ثبت‌نام مشتری (۲۰۲۶-۱۰-۱۱) — هنوز حسابی وجود ندارد، پس شماره مستقیم داده می‌شود (نه شناسه‌ی کاربر).
 * همان قالب و هزینه‌ی SendPhoneVerificationCodeJob: پیامک احراز هویت، هزینه‌ی پلتفرم، صف otp.
 */
class SendRegistrationCodeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 15;

    public function __construct(
        public readonly string $phone,
        public readonly string $code,
        public readonly int $salonId,
    ) {
        $this->onQueue(Queues::OTP);
    }

    public function handle(SMSService $smsService): void
    {
        $sent = $smsService->sendAuthTemplate($this->phone, config('services.kavenegar.templates.register_verify'), [$this->code], $this->salonId);

        if (! $sent) {
            Log::error('SendRegistrationCodeJob: failed to send the registration code', ['phone' => $this->phone, 'salon_id' => $this->salonId]);
        }
    }
}
