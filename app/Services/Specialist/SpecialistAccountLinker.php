<?php

namespace App\Services\Specialist;

use App\Models\Specialist;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Support\CurrentSalon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ⭐ حساب متخصصی که مدیر با شماره‌ی تازه اضافه کرده (آزمون دستی ۲۰۲۶-۱۰-۲۲).
 *
 * «افزودن متخصص» برای شماره‌ای که حساب ندارد فقط رکورد specialists (بی user_id) می‌سازد. صفحه‌ی ثبت‌نام کادر از ۲۰۲۶-۱۰-۱۱ حذف شده
 * بود و ثبت‌نام در صفحه‌ی سالن حساب «مشتری» می‌ساخت که به متخصص وصل نمی‌شد ← متخصص تازه هیچ راهی برای ورود نداشت.
 * تصمیم ابوالفضل: (۱) رمز اولیه‌ی اختیاری در فرم مدیر، وگرنه فعال‌سازی با کد پیامکی (وب و اپ همکار)؛ (۲) اگر متخصص در صفحه‌ی همان سالن
 * ثبت‌نام کند یا حساب مشتری همان سالن را داشته باشد، همان حساب کادر می‌شود و به متخصص وصل می‌شود (مثل رفتار «افزودن متخصص»
 * با مشتری موجود: AdminSpecialistService::matchAndPromoteUser).
 */
class SpecialistAccountLinker
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly CurrentSalon $currentSalon,
    ) {}

    /** متخصص‌های بی‌حساب با این شماره (همه‌ی سالن‌ها، یا فقط یک سالن) */
    public function pending(string $phone, ?int $salonId = null): Collection
    {
        return $this->currentSalon->allSalons(fn () => Specialist::query()
            ->where('phone', $phone)->whereNull('user_id')
            ->when($salonId !== null, fn ($q) => $q->where('salon_id', $salonId))
            ->orderBy('id')->get());
    }

    public function hasPending(string $phone, ?int $salonId = null): bool
    {
        return $this->pending($phone, $salonId)->isNotEmpty();
    }

    /** حساب کادر: همه‌ی متخصص‌های بی‌حساب همین شماره را به آن وصل می‌کند (بعد از ورود کادر یا ساخت حساب) */
    public function linkPending(User $staff): int
    {
        if ($staff->user_type !== 'staff') {
            return 0;
        }

        $ids = $this->pending($staff->phone)->pluck('id');
        if ($ids->isEmpty()) {
            return 0;
        }

        $this->currentSalon->allSalons(fn () => Specialist::query()->whereKey($ids)->whereNull('user_id')->update(['user_id' => $staff->id]));
        Log::info('Specialist records linked to staff account', ['user_id' => $staff->id, 'specialist_ids' => $ids->all()]);

        return $ids->count();
    }

    /**
     * مشتری همان سالنی که متخصصِ بی‌حساب با همین شماره دارد ← همان حساب کادر و وصل می‌شود (نوبت‌ها و کیف پولش می‌ماند).
     * اگر این شماره از قبل حساب کادر دارد (یکتایی کادر در کل پلتفرم)، مشتری دست نمی‌خورد. true = کادر شد.
     */
    public function promoteCustomer(User $customer): bool
    {
        if ($customer->user_type !== 'customer' || $customer->salon_id === null
            || ! $this->hasPending($customer->phone, (int) $customer->salon_id)
            || $this->userRepository->findStaffByPhone($customer->phone) !== null) {
            return false;
        }

        DB::transaction(function () use ($customer) {
            $customer->forceFill(['user_type' => 'staff'])->save();
            $this->linkPending($customer);
        });
        Log::info('Customer account promoted to specialist', ['user_id' => $customer->id, 'salon_id' => $customer->salon_id]);

        return true;
    }

    /**
     * ساخت حساب متخصص بعد از تأیید کد (فعال‌سازی). مشتری همان سالن ← همان حساب؛ وگرنه حساب کادر تازه با نام رکورد متخصص.
     *
     * @param  array{phone: string, password_hash: string, strength: int|null}  $data
     */
    public function activate(array $data): ?User
    {
        return DB::transaction(function () use ($data) {
            $specialists = $this->pending($data['phone']);
            if ($specialists->isEmpty() || $this->userRepository->findStaffByPhone($data['phone']) !== null) {
                return null;
            }

            $attributes = [
                'password' => $data['password_hash'],
                'password_changed_at' => now(),
                'password_strength_score' => $data['strength'],
                'phone_verified_at' => now(),
            ];

            $customer = User::query()->where('phone', $data['phone'])->where('user_type', 'customer')
                ->whereIn('salon_id', $specialists->pluck('salon_id'))->orderBy('id')->lockForUpdate()->first();

            $user = $customer ?? new User;
            $user->forceFill($attributes + [
                'name' => $customer?->name ?: $specialists->first()->name,
                'phone' => $data['phone'],
                'salon_id' => $customer?->salon_id ?? $specialists->first()->salon_id,
                'user_type' => 'staff',
            ])->save();

            $this->linkPending($user);

            return $user;
        });
    }
}
