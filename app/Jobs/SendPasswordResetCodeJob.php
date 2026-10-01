<?php

namespace App\Jobs;

use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\SMSService;
use App\Support\Queues;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * کد بازیابی رمز (کارمند و مشتری) — مثل سه کد تأیید دیگر روی صف otp، نه داخل خود درخواست.
 * سالن (برای otp_count) در زمان درخواست حساب و همراه job فرستاده می‌شود؛ worker سالن جاری ندارد.
 */
class SendPasswordResetCodeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 15;

    public function __construct(
        protected int $userId,
        protected string $code,
        protected ?int $salonId = null,
    ) {
        $this->onQueue(Queues::OTP);
    }

    public function salonId(): ?int
    {
        return $this->salonId;
    }

    public function handle(SMSService $smsService, UserRepositoryInterface $userRepository): void
    {
        $user = $userRepository->find($this->userId);

        if (! $user) {
            Log::warning('SendPasswordResetCodeJob: user not found, skipping SMS', [
                'user_id' => $this->userId,
            ]);

            return;
        }

        $template = config('services.kavenegar.templates.reset_password', 'verification');

        // کد تأیید: خرج پلتفرم، در otp_count سالن شمرده می‌شود (تصمیم ۲۰۲۶-۰۹-۳۰)
        $result = $smsService->sendAuthTemplate($user->phone, $template, [$this->code], $this->salonId);

        if (! $result) {
            Log::error('SendPasswordResetCodeJob: failed to send password reset code', [
                'user_id' => $user->id,
                'phone' => $user->phone,
            ]);
        }
    }
}
