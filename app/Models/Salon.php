<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ⭐ Phase 1 SaaS multi-tenant (feat/saas-multi-tenant-salons, commit 2). See the "⭐⭐ فیچر
 * برنامه‌ریزی‌شده (بازنگری نهایی — SaaS چندسالنی)" section of Rasta_unified_prompt.md for the
 * full architecture. `slug` is the salon's immutable public URL (/s/{slug}); `name` is the
 * display name its own admin can rename later — kept as two separate columns specifically so
 * renaming never breaks a bookmarked/SMS'd link.
 */
class Salon extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'tagline',
        'bio',
        'address',
        'phone',
        'established_year',
        'working_hours',
        'slug',
        'zarinpal_merchant_id',
        'max_specialists_count',
        'module_permissions',
        'sms_quota_per_month',
        'subscription_type',
        'subscription_started_at',
        'subscription_ends_at',
        'trial_ends_at',
        'is_suspended',
        'created_by',
    ];

    protected $casts = [
        'module_permissions' => 'array',
        'working_hours' => 'array',
        'established_year' => 'integer',
        'subscription_started_at' => 'datetime',
        'subscription_ends_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'is_suspended' => 'boolean',
    ];

    /**
     * v1: exactly one 'owner' row per salon (enforced in SuperAdminService::createSalonWithAdmin(),
     * not here — see salon_admins migration docblock for why this is an app-level rule rather
     * than a DB constraint). Phase 2 ("چند ادمین روی یک سالن") adds 'staff' rows to the same table.
     */
    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'salon_admins')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function owner(): ?User
    {
        return $this->admins()->wherePivot('role', 'owner')->first();
    }

    public function specialists(): HasMany
    {
        return $this->hasMany(Specialist::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * ⭐ فاز ۲، محور «۱. پرداخت آنلاین و صورتحساب» — تاریخچه‌ی خرید/تمدید اشتراک این سالن
     * (هم مسیر آنلاین زرین‌پال، هم تمدیدهای دستی سوپر ادمین). به InvoiceService نگاه کن.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Deliberately separate from is_suspended — a salon can be un-suspended but still past its
     * subscription_ends_at, or suspended while its subscription is technically still active
     * (a manual super-admin action, e.g. pending a support issue). Both middleware checks
     * (EnsureAdminSalonActive, ResolveSalonFromRoute) test both conditions independently.
     */
    public function hasActiveSubscription(): bool
    {
        return ! $this->is_suspended && $this->subscription_ends_at->isFuture();
    }

    /**
     * ⭐ فیچر «دوره‌ی آزمایشی رایگان» (۲۰۲۶-۰۹-۲۳): «هنوز در دوره‌ی آزمایشی» یعنی آزمایشی گرفته،
     * هنوز تموم نشده، و هیچ فاکتور پرداخت‌شده‌ای (آنلاین یا دستی سوپرادمین) نداره. به‌محض اولین
     * خرید، سالن دیگه «آزمایشی» حساب نمی‌شه (و SuperAdminService::renewSubscription خودِ
     * trial_ends_at رو هم به لحظه‌ی خرید می‌آره — به subscriptionPeriodBase() نگاه کن).
     */
    public function isOnTrial(): bool
    {
        return $this->trial_ends_at !== null
            && $this->trial_ends_at->isFuture()
            && ! $this->hasPaidInvoice();
    }

    /**
     * ⭐ تصمیم ابوالفضل (۲۰۲۶-۰۹-۲۳، جایگزین رفتار قبلی «روزهای آزمایشی سوخت نمی‌شن»): خرید در طول
     * دوره‌ی آزمایشی، اشتراک پولی رو از همون لحظه‌ی خرید شروع می‌کنه و دوره‌ی آزمایشی همون‌جا تموم
     * می‌شه. خارج از آزمایشی، رفتار تمدید مثل قبله: اگه اشتراک هنوز فعاله، دوره‌ی جدید بعد از
     * پایانش شروع می‌شه؛ اگه منقضی شده، از همین حالا. SuperAdminService::renewSubscription و
     * InvoiceService (period_start فاکتور) هر دو از همین یک متد می‌خونن تا هیچ‌وقت از هم جدا نشن.
     * باید قبل از علامت‌خوردن فاکتور جاری به‌عنوان paid صدا زده بشه (isOnTrial به hasPaidInvoice وابسته‌ست).
     */
    public function subscriptionPeriodBase(): \Illuminate\Support\Carbon
    {
        if ($this->isOnTrial()) {
            return now();
        }

        return $this->subscription_ends_at?->isFuture() ? $this->subscription_ends_at->copy() : now();
    }

    public function trialDaysLeft(): int
    {
        if (! $this->isOnTrial()) {
            return 0;
        }

        return max(1, (int) ceil(now()->diffInSeconds($this->trial_ends_at) / 86400));
    }

    public function hasPaidInvoice(): bool
    {
        // ⭐ withoutGlobalScope('salon'): خودِ رابطه از قبل به salon_id همین سالن محدوده؛ scope
        // سراسری BelongsToSalon (بر پایه‌ی CurrentSalon درخواست جاری) اینجا فقط می‌تونه غلط جواب
        // بده — مثلاً وقتی سوپرادمین یا یک Job سالن دیگه‌ای رو بررسی می‌کنه.
        return $this->invoices()->withoutGlobalScope('salon')->where('status', 'paid')->exists();
    }

    /**
     * ⭐ سهمیه‌ی پیامک کم‌ترِ دوره‌ی آزمایشی (billing.trial_sms_quota) از طریق همون override
     * موجود sms_quota_per_month اعمال می‌شه — نه یک مسیر جدا در SmsQuotaService. برای این‌که
     * برگردوندنش بعد از اولین خرید هیچ‌وقت یک override دستیِ سوپرادمین رو پاک نکنه، فقط وقتی
     * «در اثر آزمایشیه» حساب می‌شه که مقدارش دقیقاً همون عدد آزمایشی باشه و هنوز هیچ فاکتور
     * پرداخت‌شده‌ای وجود نداشته باشه.
     */
    public function isTrialSmsQuotaInEffect(): bool
    {
        return $this->trial_ends_at !== null
            && $this->sms_quota_per_month !== null
            && (int) $this->sms_quota_per_month === (int) config('billing.trial_sms_quota')
            && ! $this->hasPaidInvoice();
    }

    /**
     * ⭐ آدرس عمومی رزرو آنلاین سالن (۲۰۲۶-۰۹-۲۳) — همون لینکی که مالک سالن به مشتری‌هاش می‌ده.
     * route('home') عمداً: وقتی CENTRAL_DOMAIN پره، آخرین ثبت این نام همون نسخه‌ی ساب‌دامینیه
     * (routes/web.php)، پس URL مطلق `{slug}.{central_domain}` ساخته می‌شه؛ وقتی خالیه، همون
     * `/s/{slug}` قدیمی. یعنی این متد هیچ‌وقت خودش تصمیم routing نمی‌گیره.
     */
    public function publicUrl(): string
    {
        return route('home', ['salon_slug' => $this->slug]);
    }

    /**
     * مسیر قدیمی/همیشه‌زنده‌ی `/s/{slug}` روی همون هاستی که درخواست جاری روشه — وقتی ساب‌دامین
     * فعاله به‌عنوان لینک جایگزین نشون داده می‌شه (مثلاً اگه DNS ساب‌دامین هنوز آماده نباشه).
     */
    public function legacyPublicUrl(): string
    {
        return url('/s/'.$this->slug);
    }

    /**
     * ⭐ اطلاعات تماس و فعالیت سالن (۲۰۲۶-۰۹-۲۳): «سال تجربه» از سال شروع فعالیت محاسبه می‌شه
     * (نه یک عدد ثابت)، تا هر سال خودکار به‌روز بمونه. null = سالن هنوز واردش نکرده.
     */
    public function experienceYears(): ?int
    {
        return $this->established_year === null ? null : max(0, now()->year - $this->established_year);
    }

    public static function establishedYearFromExperience(int $years): int
    {
        return now()->year - max(0, $years);
    }

    /** خطوط نمایشی ساعات کاری (خالی = هنوز وارد نشده) — به SalonWorkingHours::lines نگاه کن. */
    public function workingHoursLines(): array
    {
        return \App\Support\SalonWorkingHours::lines($this->working_hours);
    }
}
