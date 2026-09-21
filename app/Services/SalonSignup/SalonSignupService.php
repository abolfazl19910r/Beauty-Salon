<?php

namespace App\Services\SalonSignup;

use App\Models\Salon;
use App\Models\User;
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
 * max_specialists_count عمداً از کاربر پرسیده نمی‌شه (این یک مفهوم داخلی SaaS است، نه چیزی که
 * یک بازدیدکننده‌ی عادی معنی‌اش رو بدونه) — از config('billing.default_max_specialists_count')
 * گرفته می‌شه؛ تغییرش بعداً هنوز فقط از طریق سوپرادمین (SuperAdminController::update) ممکنه —
 * محدودیت از قبل موجود پروژه، نه چیزی که این فیچر اضافه کرده باشه.
 *
 * owner برخلاف AdminUserService::create() (که وقتی is_active=true باشه، phone_verified_at
 * بلافاصله ست می‌شه چون سوپرادمین خودش تاییدکننده‌ست) عمداً phone_verified_at=null می‌مونه —
 * این سرویس فقط ردیف‌ها رو می‌سازه؛ ارسال/تایید کد OTP مسئولیت SalonSignupController با همون
 * PhoneVerificationService به‌کاررفته در CustomerRegisteredController است.
 */
class SalonSignupService
{
    public function __construct(
        protected readonly PasswordStrengthService $passwordStrengthService,
        protected readonly UserRepositoryInterface $userRepository,
    ) {}

    /**
     * @return array{salon: Salon, owner: User}
     */
    public function register(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $salon = Salon::create([
                'name' => $data['name'],
                'slug' => $data['slug'],
                'max_specialists_count' => (int) config('billing.default_max_specialists_count', 3),
                'subscription_type' => $data['subscription_type'],
                'subscription_started_at' => now(),
                // ⭐ عمدی، نه باگ — به docblock بالای این کلاس نگاه کن.
                'subscription_ends_at' => now()->subSecond(),
                'is_suspended' => false,
                'created_by' => null,
            ]);

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

            return ['salon' => $salon->fresh(), 'owner' => $owner];
        });
    }
}
