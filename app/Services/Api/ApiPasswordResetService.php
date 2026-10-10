<?php

namespace App\Services\Api;

use App\Jobs\SendPasswordResetCodeJob;
use App\Models\User;
use App\Support\Queues;
use App\Support\SalonOfNotifiable;
use Illuminate\Support\Facades\Hash;

/**
 * بازیابی رمز کادر از اپ همکار (بسته‌ی ۲) — همان سازوکار challenge ورود (۵ کد غلط، فاصله و سقف ارسال دوباره،
 * اتصال به اپ) ولی روی کد بازیابی وب: ستون verification_code، مهلت auth.reset_code_expire_minutes،
 * SendPasswordResetCodeJob روی صف otp (هزینه‌ی پلتفرم، مثل /forgot-password). ذخیره‌ی رمز تازه همه‌ی توکن‌ها را
 * باطل می‌کند (هوک User، بسته‌ی ۱)، پس اپ بعدش دوباره وارد می‌شود.
 */
class ApiPasswordResetService extends ApiLoginService
{
    protected const KEY_PREFIX = 'api-reset:';

    protected const CODE_FIELD = 'verification_code';

    protected const CODE_EXPIRES_FIELD = 'verification_code_expire_at';

    protected function deliverCode(User $user): void
    {
        $code = (string) random_int(100000, 999999);

        $user->forceFill([
            'verification_code' => $code,
            'verification_code_expire_at' => now()->addMinutes((int) config('auth.reset_code_expire_minutes', 2)),
        ])->save();

        Queues::dispatchOtp(new SendPasswordResetCodeJob($user->id, $code, $user->salon_id ?? SalonOfNotifiable::resolve($user)));
    }

    protected function consumeCode(User $user, string $code): bool
    {
        // رمز تازه را resetPassword ذخیره می‌کند؛ اینجا فقط کد یک‌بارمصرف می‌شود
        $user->forceFill(['verification_code' => null, 'verification_code_expire_at' => null])->save();

        return true;
    }

    protected function logAttempt(bool $success, User $user): void
    {
        // شکست کد بازیابی مثل ورود ناموفق ثبت می‌شود (حدس کد)؛ موفقیتش ورود نیست
        if (! $success) {
            $this->securityLogService->logLogin(false, $user->phone, $user);
        }
    }

    public function resetPassword(string $challenge, string $code, string $audience, string $password): User
    {
        $user = $this->verify($challenge, $code, $audience, null);
        $user->forceFill(['password' => Hash::make($password)])->save();

        return $user;
    }
}
