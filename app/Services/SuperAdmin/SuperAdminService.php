<?php

namespace App\Services\SuperAdmin;

use App\Models\Salon;
use App\Models\Specialist;
use App\Models\User;
use App\Services\Admin\User\AdminUserService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
    public function __construct(protected readonly AdminUserService $adminUserService) {}

    /**
     * @param  array{name:string, slug:string, subscription_type:string, max_specialists_count:int, module_permissions:?array, admin_name:string, admin_phone:string, admin_password:string}  $data
     */
    public function createSalonWithAdmin(array $data, User $createdBy): Salon
    {
        return DB::transaction(function () use ($data, $createdBy) {
            $salon = Salon::create([
                'name' => $data['name'],
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
        $currentSpecialistCount = Specialist::where('salon_id', $salon->id)->count();

        if ($data['max_specialists_count'] < $currentSpecialistCount) {
            throw new \InvalidArgumentException(
                "سقف جدید ({$data['max_specialists_count']}) نمی‌تواند کمتر از تعداد متخصصین فعلی ({$currentSpecialistCount}) باشد."
            );
        }

        $salon->update([
            'name' => $data['name'],
            'max_specialists_count' => $data['max_specialists_count'],
            'module_permissions' => $data['module_permissions'] ?? null,
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
        $current = Specialist::where('salon_id', $salon->id)->count();

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
