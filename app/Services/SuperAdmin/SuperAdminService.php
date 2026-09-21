<?php

namespace App\Services\SuperAdmin;

use App\Models\Salon;
use App\Models\User;
use App\Repositories\Contracts\SalonRepositoryInterface;
use App\Repositories\Contracts\SpecialistRepositoryInterface;
use App\Services\Admin\User\AdminUserService;
use Illuminate\Support\Facades\DB;

/**
 * ⭐ Phase 1 SaaS multi-tenant (feat/saas-multi-tenant-salons, commit 5). See "⭐⭐ فیچر
 * برنامه‌ریزی‌شده (بازنگری نهایی — SaaS چندسالنی)" in Rasta_unified_prompt.md for the full
 * architecture this implements.
 *
 * Reuses App\Services\Admin\User\AdminUserService::create() for the actual User row rather than
 * duplicating that logic — matching this project's own DRY precedent (documented in the SaaS
 * design table: "هم‌ترازی با AdminUserService"). This service's own job is strictly the
 * salon-management layer on top: the salon record, the salon_admins link, subscription math,
 * and quota/suspension rules — not user creation itself.
 */
class SuperAdminService
{
    public function __construct(
        protected readonly AdminUserService $adminUserService,
        protected readonly SalonRepositoryInterface $salonRepository,
        protected readonly SpecialistRepositoryInterface $specialistRepository,
    ) {}

    /**
     * @param  array{name:string, slug:string, subscription_type:string, max_specialists_count:int, module_permissions:?array, admin_name:string, admin_phone:string, admin_password:string}  $data
     */
    public function createSalonWithAdmin(array $data, User $createdBy): Salon
    {
        return DB::transaction(function () use ($data, $createdBy) {
            $salon = $this->salonRepository->create([
                'name' => $data['name'],
                'tagline' => $data['tagline'] ?? null,
                'bio' => $data['bio'] ?? null,
                'slug' => $data['slug'],
                'max_specialists_count' => $data['max_specialists_count'],
                'module_permissions' => $data['module_permissions'] ?? null,
                'subscription_type' => $data['subscription_type'],
                'subscription_started_at' => now(),
                'subscription_ends_at' => $this->computeSubscriptionEnd($data['subscription_type']),
                'is_suspended' => false,
                'created_by' => $createdBy->id,
            ]);

            $admin = $this->adminUserService->create([
                'name' => $data['admin_name'],
                'phone' => $data['admin_phone'],
                'password' => $data['admin_password'],
                'is_admin' => true,
                'is_active' => true,
                'roles' => [],
            ]);

            // ⭐ App-level rule only, deliberately not a DB constraint (see the salon_admins
            // migration docblock) — "at most one owner per salon" is what phase 2 ("چند ادمین
            // روی یک سالن") lifts, without any migration against live data, by removing exactly
            // this check.
            $salon->admins()->attach($admin->id, ['role' => 'owner']);

            return $salon->fresh('admins');
        });
    }

    /**
     * @param  array{name:string, max_specialists_count:int, module_permissions:?array}  $data
     */
    public function updateSalon(Salon $salon, array $data): Salon
    {
        // ⭐ "کاهش سقف زیر تعداد فعلی ممنوع" — enforced here too (not just at the request-
        // validation layer) since this method could in principle be called from elsewhere.
        // ⭐ Bug found by actually running SalonManagementTest on real PHP (commit 8): a plain
        // Specialist::where('salon_id', ...) still goes through BelongsToSalon's global 'salon'
        // scope, which ANDs in app(CurrentSalon::class)->id() whenever it happens to be set to
        // some OTHER salon (e.g. via the base TestCase's default binding, or any future code
        // path that sets CurrentSalon before calling into this service) — the two salon_id
        // filters can never both match, so the count silently comes back 0 and the "cannot
        // lower quota below current specialist count" guard below never fires. In real
        // production /superadmin requests this scope is never actually populated (EnsureSuperAdmin
        // never calls CurrentSalon::set()), so this never broke production traffic — but the
        // query itself was still wrong, and the same "global scope stacks with an explicit
        // filter on the same column" footgun this project has hit before (WalletSetting,
        // AdminWallet). withoutGlobalScope('salon') makes this explicitly cross-tenant, matching
        // what a super admin operation actually is.
        $currentSpecialistCount = $this->specialistRepository->countBySalonIgnoringScope($salon->id);

        if ($data['max_specialists_count'] < $currentSpecialistCount) {
            throw new \InvalidArgumentException(
                "سقف جدید ({$data['max_specialists_count']}) نمی‌تواند کمتر از تعداد متخصصین فعلی ({$currentSpecialistCount}) باشد."
            );
        }

        $salon->update([
            'name' => $data['name'],
            // ⭐ پیگیری «محور ۳» — array_key_exists (نه isset)، هم‌الگو با zarinpal_merchant_id
            // پایین‌تر: فرستادن مقدار خالی باید واقعاً tagline/bio رو پاک کنه، نه بی‌اثر بمونه.
            'tagline' => array_key_exists('tagline', $data) ? ($data['tagline'] ?: null) : $salon->tagline,
            'bio' => array_key_exists('bio', $data) ? ($data['bio'] ?: null) : $salon->bio,
            'max_specialists_count' => $data['max_specialists_count'],
            'module_permissions' => $data['module_permissions'] ?? null,
            // ⭐ فاز ۲، مورد ۹ — array_key_exists (نه isset) عمداً: سوپر ادمین باید بتواند یک
            // merchant_id قبلاً ثبت‌شده را با فرستادن مقدار خالی دوباره null کند (بازگشت به
            // fallback سراسری)، نه اینکه مقدار قبلی برای همیشه بماند چون کلید خالی "ست‌نشده"
            // به‌حساب بیاید.
            'zarinpal_merchant_id' => array_key_exists('zarinpal_merchant_id', $data)
                ? ($data['zarinpal_merchant_id'] ?: null)
                : $salon->zarinpal_merchant_id,
        ]);

        return $salon;
    }

    /**
     * ⭐ "از max(now(), subscription_ends_at) جمع زده می‌شه" — تمدید همیشه از دیرترین تاریخ
     * (الان یا پایان فعلی) شروع می‌شه، نه از الان، تا تمدید زودهنگام دوره‌ی باقی‌مانده رو دور
     * نریزه.
     */
    public function renewSubscription(Salon $salon, string $subscriptionType): Salon
    {
        $base = $salon->subscription_ends_at->isFuture() ? $salon->subscription_ends_at : now();

        $salon->update([
            'subscription_type' => $subscriptionType,
            'subscription_ends_at' => $this->addSubscriptionPeriod($base, $subscriptionType),
        ]);

        return $salon;
    }

    public function toggleSuspend(Salon $salon): Salon
    {
        // ⭐ سالن سیستم (پیش‌فرض) هرگز نباید تعلیق بشه — طبق تصمیم مستندشده در بخش SaaS.
        if ($salon->slug === 'rasta') {
            throw new \InvalidArgumentException('سالن پیش‌فرض سیستم قابل تعلیق نیست.');
        }

        $salon->update(['is_suspended' => ! $salon->is_suspended]);

        return $salon;
    }

    public function remainingSpecialistQuota(Salon $salon): int
    {
        // ⭐ Same fix as updateSalon() above — see that docblock.
        $current = $this->specialistRepository->countBySalonIgnoringScope($salon->id);

        return max(0, $salon->max_specialists_count - $current);
    }

    private function computeSubscriptionEnd(string $subscriptionType): \Illuminate\Support\Carbon
    {
        return $this->addSubscriptionPeriod(now(), $subscriptionType);
    }

    private function addSubscriptionPeriod(\Illuminate\Support\Carbon $from, string $subscriptionType): \Illuminate\Support\Carbon
    {
        return match ($subscriptionType) {
            '1m' => $from->copy()->addMonth(),
            '3m' => $from->copy()->addMonths(3),
            '6m' => $from->copy()->addMonths(6),
            '12m' => $from->copy()->addMonths(12),
            default => throw new \InvalidArgumentException("نوع اشتراک نامعتبر: {$subscriptionType}"),
        };
    }
}
