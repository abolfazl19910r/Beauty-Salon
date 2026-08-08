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

class SendLoginVerificationCodeJob implements ShouldQueue
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
            Log::warning('SendLoginVerificationCodeJob: user not found, skipping SMS', [
                'user_id' => $this->userId,
            ]);

            return;
        }

        $template = config('services.kavenegar.templates.login_verify');

        $result = $smsService->sendTemplate($user->phone, $template, [$this->code]);

        if (! $result) {
            Log::error('SendLoginVerificationCodeJob: failed to send login verification code', [
                'user_id' => $user->id,
                'phone' => $user->phone,
            ]);
        }
    }
}
