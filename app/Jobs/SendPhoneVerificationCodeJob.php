<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\SMSService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * ⭐ Fix (test-writing session 10): PhoneVerificationService::sendCode() used to call
 * SMSService::sendTemplate() directly and synchronously — the exact same bug class
 * already fixed for the login flow (SendLoginVerificationCodeJob) but never applied
 * here, meaning a slow/unreachable Kavenegar could still block the request for
 * 20-30+ seconds on registration, and (now that EnsurePhoneIsVerified is wired up for
 * real) on every first protected-page visit by a not-yet-verified user too.
 */
class SendPhoneVerificationCodeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    // Job's own time limit — even if Kavenegar responds slowly, the Worker won't wait forever for this Job
    public int $timeout = 15;

    public function __construct(
        protected int $userId,
        protected string $code
    ) {}

    public function handle(SMSService $smsService): void
    {
        $user = User::find($this->userId);

        if (! $user) {
            Log::warning('SendPhoneVerificationCodeJob: user not found, skipping SMS', [
                'user_id' => $this->userId,
            ]);

            return;
        }

        $template = config('services.kavenegar.templates.register_verify');

        $result = $smsService->sendTemplate($user->phone, $template, [$this->code]);

        if (! $result) {
            Log::error('SendPhoneVerificationCodeJob: failed to send phone verification code', [
                'user_id' => $user->id,
                'phone' => $user->phone,
            ]);
        }
    }
}
