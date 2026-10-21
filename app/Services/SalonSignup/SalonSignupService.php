<?php

namespace App\Services\SalonSignup;

use App\Models\Salon;
use App\Models\User;
use App\Repositories\Contracts\SalonRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\PasswordStrengthService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * ⭐ فاز ۲ SaaS، محور «۴. ثبت‌نام عمومی سالن (self-service)».
 *
 * برخلاف SuperAdminService::createSalonWithAdmin() — که subscription_ends_at رو واقعی محاسبه
 * می‌کنه چون سوپرادمین دستی و آگاهانه یک دوره‌ی خریداری‌شده رو ثبت می‌کنه — اینجا
 * subscription_ends_at عمداً روی یک لحظه‌ی همین‌الان-ولی-گذشته (now()->subSecond()) ست می‌شه.
 * این یک باگ نیست: به‌جای یک migration/ستون جدید («سالن در انتظار پرداخت»)، از رفتار از قبل
 * موجود EnsureAdminSalonActive استفاده می‌کنه — یک سالن با subscription منقضی (و
 * is_suspended=false) بدون هیچ کد جدیدی ادمینش رو مستقیم به admin.billing.index هدایت می‌کنه و
 * اجازه‌ی دسترسی به هیچ بخش دیگه‌ای از پنل رو تا پرداخت واقعی نمی‌ده — دقیقاً رفتاری که یک سالن
 * self-service «هنوز پول نداده» باید داشته باشه. subscription_type انتخابی کاربر همینجا ذخیره
 * می‌شه (فقط برای پیش‌انتخاب پلن روی صفحه‌ی billing)، ولی «خریداری‌شده» فقط بعد از اولین پرداخت
 * موفق محسوب می‌شه (AdminBillingController::callback → InvoiceService::markPaidFromGateway) —
 * که چون subscription_ends_at از قبل گذشته، period_start هم درست از now() محاسبه می‌شه (به
 * InvoiceService::markPaidFromGateway نگاه کن، همون فالبک «اگه گذشته، از الان»).
 *
 * max_specialists_count همان «تعداد متخصص‌ها»یی است که فرم ثبت‌نام می‌پرسد (۲۰۲۶-۰۹-۳۰)؛ قیمت اشتراک بر اساس
 * همین عدد حساب می‌شه (SubscriptionPricing). تغییرش بعداً از طریق سوپرادمین (SuperAdminController::update).
 *
 * owner برخلاف AdminUserService::create() (که وقتی is_active=true باشه، phone_verified_at
 * بلافاصله ست می‌شه چون سوپرادمین خودش تاییدکننده‌ست) عمداً phone_verified_at=null می‌مونه —
 * این سرویس فقط ردیف‌ها رو می‌سازه؛ از ۲۰۲۶-۱۰-۱۱ فقط SalonSignupChallenge صدایش می‌زند، بعد از تأیید کد (و
 * phone_verified_at مالک را همان‌جا ست می‌کند).
 */
class SalonSignupService
{
    public function __construct(
        protected readonly PasswordStrengthService $passwordStrengthService,
        protected readonly UserRepositoryInterface $userRepository,
        protected readonly SalonRepositoryInterface $salonRepository,
    ) {}

    /**
     * @return array{salon: Salon, owner: User}
     */
    public function register(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $salon = $this->createSalon($data, $this->trialDays());
            $owner = $this->userRepository->create([
                'name' => $data['owner_name'],
                'phone' => $data['owner_phone'],
                'password' => Hash::make($data['owner_password']),
                'password_changed_at' => now(),
                'password_strength_score' => $this->passwordStrengthService->score($data['owner_password']),
                'is_admin' => true,
                'user_type' => 'staff',
                'salon_id' => null,
            ]);

            // ⭐ همون قانون آنتی-race سوپرادمین («فقط یک owner») — به docblock
            // SuperAdminService::createSalonWithAdmin() نگاه کن؛ اینجا هم صادقه چون سالن تازه
            // ساخته شده، عملاً هیچ owner دیگری تا این لحظه وجود نداره.
            $salon->admins()->attach($owner->id, ['role' => 'owner']);

            app(\App\Services\SuperAdmin\SuperAdminNotifier::class)->send('signup', sprintf('سالن تازه ثبت‌نام کرد: «%s» (/s/%s)، مالک %s.', $salon->name, $salon->slug, $owner->phone),
                route('superadmin.salons.edit', $salon->id, false), (int) $salon->id);

            return ['salon' => $salon->fresh(), 'owner' => $owner];
        });
    }

    /** سالن تازه؛ $trialDays = ۰ یعنی بدون دوره‌ی رایگان (صفحه‌ی صورتحساب تا اولین پرداخت) */
    private function createSalon(array $data, int $trialDays): Salon
    {
        // پلن آزمایشی (بسته‌ی پلن‌ها، ۲۰۲۶-۱۰-۱۴): سهمیه و امکانات از خود پلن؛ بدون جدول پلن (پیش از plans:sync-defaults) همان
        // override قدیمی سهمیه‌ی آزمایشی از config.
        $trialPlan = $trialDays > 0 ? \App\Models\Plan::trial() : null;

        return $this->salonRepository->create([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'max_specialists_count' => (int) $data['specialists_count'],
            'subscription_type' => $data['subscription_type'] ?? '1m',
            'subscription_started_at' => now(),
            // ⭐ عمدی، نه باگ — به docblock بالای این کلاس نگاه کن. با دوره‌ی آزمایشی روشن
            // (۲۰۲۶-۰۹-۲۳)، به‌جای «از قبل منقضی»، سالن همین الان تا پایان آزمایشی کاملاً فعاله
            // و همون EnsureAdminSalonActive موجود بعد از پایانش خودکار به billing می‌فرسته.
            'subscription_ends_at' => $trialDays > 0 ? now()->addDays($trialDays) : now()->subSecond(),
            'trial_ends_at' => $trialDays > 0 ? now()->addDays($trialDays) : null,
            'plan_id' => $trialPlan?->id,
            'sms_quota_per_month' => $trialDays > 0 && ! $trialPlan ? (int) config('billing.trial_sms_quota') : null,
            'is_suspended' => false,
            'created_by' => null,
            // ⭐ ۲۰۲۶-۰۹-۲۳: آدرس/تلفن/سال شروع فعالیت/ساعات کاری — از
            // StoreSalonSignupRequest::salonContactAttributes().
            ...($data['contact'] ?? []),
            'zarinpal_merchant_id' => \App\Support\ZarinpalMerchant::normalize($data['zarinpal_merchant_id'] ?? null),
        ]);
    }

    /**
     * ⭐ سالن دوم (و بعدی) یک حساب کادرِ موجود (تصمیم ۵، ۲۰۲۶-۱۰-۲۲): بدون ساختن حساب و بدون کد پیامکی (کاربر وارد شده است)،
     * بدون دوره‌ی رایگان (۵-۲ الف: فقط اولین سالن هر شماره) — مستقیم صفحه‌ی صورتحساب یا درگاه.
     */
    public function registerForExistingOwner(array $data, User $owner, ?\Illuminate\Http\UploadedFile $logo = null): Salon
    {
        $salon = DB::transaction(function () use ($data, $owner) {
            $salon = $this->createSalon($data, 0);
            app(\App\Services\Salon\SalonMembershipService::class)->addExisting($owner, $salon, 'owner');

            app(\App\Services\SuperAdmin\SuperAdminNotifier::class)->send('signup', sprintf('سالن تازه ثبت‌نام کرد: «%s» (/s/%s)، مالک %s (سالن دیگرش را هم دارد).', $salon->name, $salon->slug, $owner->phone),
                route('superadmin.salons.edit', $salon->id, false), (int) $salon->id);

            return $salon;
        });

        if ($logo) {
            app(\App\Services\Salon\SalonLogoService::class)->replace($salon, $logo);
        }

        return $salon->fresh();
    }

    /** روزهای آزمایشی از پلن آزمایشی (سوپرادمین)؛ بدون آن از config. ۰ = بدون آزمایشی */
    public function trialDays(): int
    {
        $trial = \App\Models\Plan::trial();

        return max(0, (int) ($trial && $trial->is_active ? $trial->trial_days : ($trial ? 0 : config('billing.trial_days', 0))));
    }
}
