<?php

namespace App\Services\Admin\Specialist;

use App\Exceptions\SpecialistQuotaExceededException;
use App\Models\Specialist;
use App\Models\User;
use App\Repositories\Contracts\SalonRepositoryInterface;
use App\Repositories\Contracts\SpecialistRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Support\CurrentSalon;
use Illuminate\Support\Facades\DB;

class AdminSpecialistService
{
    public function __construct(
        protected readonly CurrentSalon $currentSalon,
        protected readonly SpecialistRepositoryInterface $specialistRepository,
        protected readonly UserRepositoryInterface $userRepository,
        protected readonly SalonRepositoryInterface $salonRepository,
    ) {}

    public function create(array $validated, ?string $rawCommissionRate): array
    {
        return DB::transaction(function () use ($validated, $rawCommissionRate) {
            $services = $validated['services'];
            unset($validated['services']);

            // سقف متخصص در برابر دو ساخت هم‌زمان (۲۰۲۶-۱۰-۰۱): ردیف سالن قفل می‌شود و ساخت‌های هم‌زمان همان سالن پشت
            // سر هم اجرا می‌شوند؛ شمارش بعد از گرفتن قفل، متخصص commit‌شده‌ی درخواست قبلی را می‌بیند. سقف هم از همان ردیف
            // قفل‌شده خوانده می‌شود. ⚠️ پیش از این قفل هیچ کوئری خواندنی در این تراکنش نباشد: در REPEATABLE READ اولین
            // خواندن عادی snapshot تراکنش را می‌سازد و شمارش بعدی همان عدد قدیمی را می‌دید.
            $salon = $this->salonRepository->lockForUpdateFindOrFail($this->currentSalon->id());
            $currentCount = $this->specialistRepository->countBySalonIgnoringScope($salon->id);

            if ($currentCount >= $salon->max_specialists_count) {
                throw SpecialistQuotaExceededException::quotaReached(
                    'Specialist quota reached for salon.',
                    [
                        'salon_id' => $salon->id,
                        'current_count' => $currentCount,
                        'max_specialists_count' => $salon->max_specialists_count,
                    ]
                );
            }

            $validated['phone'] = $this->normalizePhone($validated['phone']);
            $validated['commission_rate'] = $this->parseCommissionRate($rawCommissionRate);

            $initialPassword = $validated['initial_password'] ?? null;
            unset($validated['initial_password']);

            $matchedUser = $this->matchAndPromoteUser($validated['phone']);

            // ⭐ رمز اولیه‌ی مدیر (۲۰۲۶-۱۰-۲۲): شماره‌ی بی‌حساب ← حساب کادر همین حالا (ورود اول با همین رمز و کد پیامکی)
            if (! $matchedUser && $initialPassword) {
                $matchedUser = new \App\Models\User;
                $matchedUser->forceFill([
                    'name' => $validated['name'],
                    'phone' => $validated['phone'],
                    'password' => \Illuminate\Support\Facades\Hash::make($initialPassword),
                    'salon_id' => $this->currentSalon->id(),
                    'user_type' => 'staff',
                ])->save();
                $createdAccount = true;
            }

            $validated['user_id'] = $matchedUser?->id;

            $specialist = $this->specialistRepository->create($validated);
            $specialist->services()->attach($services);

            return [
                'specialist' => $specialist,
                'matched_user' => $matchedUser,
                'created_account' => $createdAccount ?? false,
            ];
        }, attempts: 3);
    }

    /**
     * ⭐ ۲۰۲۶-۱۰-۲۹ (تصمیم ابوالفضل): اگر متخصص حساب ورود دارد، شماره‌ی تازه‌ی مدیر روی همان حساب هم می‌نشیند و مدیر همیشه می‌تواند رمز
     * بگذارد — پیش‌تر حساب شماره‌ی قدیمی را نگه می‌داشت و از پرونده‌ی متخصص جدا می‌شد («فراموشی رمز» با شماره‌ی تازه پیدا نمی‌کرد). فقط
     * برای حسابی که مال همین سالن است (accountOwnedHere)؛ نشست‌ها و توکن‌ها بسته می‌شوند (User::booted برای phone/password) و پیامک
     * خبر به متخصص می‌رود. متخصص بی حساب: همان رفتار قبلی (پیوند یا ساخت حساب با رمز اولیه).
     */
    public function update(Specialist $specialist, array $validated, ?string $rawCommissionRate): Specialist
    {
        $notices = [];

        $updated = DB::transaction(function () use ($specialist, $validated, $rawCommissionRate, &$notices) {
            $services = $validated['services'];
            unset($validated['services']);

            $validated['phone'] = $this->normalizePhone($validated['phone']);
            $validated['commission_rate'] = $this->parseCommissionRate($rawCommissionRate);

            $password = $validated['initial_password'] ?? null;
            unset($validated['initial_password']);

            $account = $this->linkedAccount($specialist);

            if ($account) {
                $notices = $this->updateAccount($account, $validated['phone'], $password);
                $validated['user_id'] = $account->id;
            } else {
                $matchedUser = $this->matchAndPromoteUser($validated['phone']);

                // ⭐ رمز اولیه‌ی مدیر (۲۰۲۶-۱۰-۲۲): شماره‌ی بی‌حساب ← حساب کادر همین حالا (ورود اول با همین رمز و کد پیامکی)
                if (! $matchedUser && $password) {
                    $matchedUser = new User;
                    $matchedUser->forceFill([
                        'name' => $validated['name'],
                        'phone' => $validated['phone'],
                        'password' => \Illuminate\Support\Facades\Hash::make($password),
                        'salon_id' => $this->currentSalon->id(),
                        'user_type' => 'staff',
                    ])->save();
                }

                $validated['user_id'] = $matchedUser?->id;
            }

            $specialist = $this->specialistRepository->update($specialist, $validated);
            $specialist->services()->sync($services);

            return $specialist;
        });

        foreach ($notices as [$phone, $text]) {
            app(\App\Services\SMSService::class)->send($phone, $text);
        }

        return $updated;
    }

    /** حساب ورود متخصص: user_id، یا (پرونده‌های قدیمی) حساب کادر با همان شماره — همان قاعده‌ی User::specialist() */
    private function linkedAccount(Specialist $specialist): ?User
    {
        if ($specialist->user_id) {
            return User::query()->lockForUpdate()->find($specialist->user_id);
        }

        return User::query()->where('phone', $specialist->phone)->where('user_type', 'staff')->lockForUpdate()->first();
    }

    /**
     * @return list<array{0: string, 1: string}> پیامک‌های خبر (بعد از commit فرستاده می‌شوند)
     */
    private function updateAccount(User $account, string $phone, ?string $password): array
    {
        $phoneChanged = $account->phone !== $phone;
        if (! $phoneChanged && ! $password) {
            return [];
        }

        $field = $phoneChanged ? 'phone' : 'initial_password';
        if (! $this->accountOwnedHere($account)) {
            throw \Illuminate\Validation\ValidationException::withMessages([$field => 'این حساب در سالن دیگری هم کار می‌کند یا مدیر است؛ شماره و رمز آن را فقط خود متخصص از پروفایلش عوض می‌کند.']);
        }

        if ($phoneChanged && $this->phoneTakenByAnotherAccount($phone, $account)) {
            throw \Illuminate\Validation\ValidationException::withMessages(['phone' => 'این شماره حساب دیگری دارد؛ اول همان حساب باید شماره‌اش را عوض کند.']);
        }

        $salonName = (string) ($this->currentSalon->get()?->name ?? '');
        $oldPhone = (string) $account->phone;
        $notices = [];

        if ($phoneChanged) {
            $account->forceFill(['phone' => $phone])->save();
            $notices[] = [$oldPhone, sprintf('مدیر %s شماره‌ی ورود حساب متخصص شما را به ...%s تغییر داد.', $salonName, substr($phone, -4))];
            $notices[] = [$phone, sprintf('حساب متخصص شما در %s از این پس با همین شماره وارد می‌شود.', $salonName)];
        }

        if ($password) {
            $account->forceFill(['password' => \Illuminate\Support\Facades\Hash::make($password)])->save();
            $notices[] = [$phone, sprintf('مدیر %s رمز ورود حساب متخصص شما را تغییر داد. اگر خبر ندارید با سالن تماس بگیرید.', $salonName)];
        }

        // همه‌ی ورودهای همین حساب: نشست وب، توکن اپ همکار، «مرا به خاطر بسپار» (تغییر رمز را User::booted هم می‌بندد؛ شماره را نه)
        DB::table('sessions')->where('user_id', $account->id)->delete();
        $account->tokens()->delete();
        $account->revokeRememberedDevices();

        app(\App\Services\SecurityLogService::class)->logAction('specialist_account_changed_by_admin', $account, [
            'phone_changed' => $phoneChanged, 'password_changed' => (bool) $password, 'salon_id' => $this->currentSalon->id(),
        ]);

        return $notices;
    }

    /** حسابی که فقط متخصص همین سالن است (نه مدیر هیچ سالنی، نه متخصص سالن دیگر) — مالک یک سالن حساب مشترک را عوض نمی‌کند */
    private function accountOwnedHere(User $account): bool
    {
        $salonId = $this->currentSalon->id();

        return $account->user_type === 'staff'
            && ($account->salon_id === null || (int) $account->salon_id === (int) $salonId)
            && ! $account->is_admin
            && ! $account->hasRole('super-admin')
            && ! $account->salons()->exists()
            && ! Specialist::withoutGlobalScopes()
                ->where('salon_id', '!=', $salonId)
                ->where(fn ($q) => $q->where('user_id', $account->id)->orWhere('phone', $account->phone))
                ->exists();
    }

    private function phoneTakenByAnotherAccount(string $phone, User $account): bool
    {
        return User::query()
            ->where('phone', $phone)
            ->whereKeyNot($account->id)
            ->where(fn ($q) => $q->where('user_type', 'staff')
                ->orWhere(fn ($q) => $q->where('user_type', 'customer')->where('salon_id', $this->currentSalon->id())))
            ->exists();
    }

    public function delete(Specialist $specialist): void
    {
        DB::transaction(function () use ($specialist) {
            $specialist->services()->detach();
            $this->specialistRepository->delete($specialist);
        });
    }

    private function matchAndPromoteUser(string $phone): ?User
    {
        $currentSalonId = $this->currentSalon->id();

        $matchedUser = $this->userRepository->findStaffByPhone($phone);

        if ($matchedUser) {
            return $matchedUser;
        }

        $matchedUser = $this->userRepository->findCustomerByPhoneInSalon($phone, $currentSalonId);

        if ($matchedUser) {
            $matchedUser->update([
                'user_type' => 'staff',
                'salon_id' => $currentSalonId,
            ]);
        }

        return $matchedUser;
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);

        if (str_starts_with($digits, '0098') && strlen($digits) === 14) {
            $digits = '0'.substr($digits, 4);
        } elseif (str_starts_with($digits, '98') && strlen($digits) === 12) {
            $digits = '0'.substr($digits, 2);
        }

        return $digits;
    }

    private function parseCommissionRate(?string $raw): ?float
    {
        return $raw !== null && $raw !== '' ? (float) $raw : null;
    }
}
