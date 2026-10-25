<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ⭐ «گزارش کارهای ابرمدیر» (۲۰۲۶-۱۰-۱۵). سطح پلتفرم، بدون BelongsToSalon (salon_id فقط «درباره‌ی کدام سالن»). فقط افزوده می‌شود.
 */
class SuperAdminAuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'superadmin_audit_logs';

    protected $fillable = ['user_id', 'user_name', 'action', 'salon_id', 'description', 'changes', 'ip_address', 'created_at'];

    protected $casts = ['changes' => 'array', 'created_at' => 'datetime'];

    /** برچسب فارسی هر نوع کار (فیلتر صفحه) */
    public const ACTIONS = [
        'salon.created' => 'ساخت سالن',
        'salon.updated' => 'ویرایش سالن',
        'salon.renewed' => 'تمدید دستی اشتراک',
        'salon.suspended' => 'تعلیق / رفع تعلیق',
        'salon.sms_credit' => 'افزودن اعتبار پیامک',
        'salon.plan' => 'تغییر پلن سالن',
        'salon.feature_override' => 'استثنای امکانات',
        'salon.owner' => 'تغییر مالک سالن',
        'salon.member_added' => 'افزودن حساب موجود به سالن',
        'salon.paused' => 'توقف موقت رزرو سالن',
        'salon.resumed' => 'بازگشایی رزرو سالن',
        'impersonation.started' => 'ورود اضطراری به پنل سالن',
        'impersonation.ended' => 'پایان ورود اضطراری',
        'plan.saved' => 'ساخت / ویرایش پلن',
        'plan.deleted' => 'حذف پلن',
        'payment.resolved' => 'رسیدگی پرداخت پلتفرم',
        'queue.retried' => 'تلاش دوباره‌ی کار صف',
        'queue.forgotten' => 'حذف کار ناموفق',
        'announcement.saved' => 'اطلاعیه‌ی سراسری',
        'announcement.deleted' => 'حذف اطلاعیه‌ی سراسری',
        'settings.updated' => 'تنظیمات پلتفرم',
        'template_access.updated' => 'دسترسی قالب‌ها',
        'push.mode' => 'حالت پوش اپ‌ها',
        'push.device_signed_out' => 'خروج اپ یک گوشی',
        'app_release.upload' => 'آپلود نسخه‌ی اپ',
        'app_release.update' => 'انتشار / پنهان کردن نسخه‌ی اپ',
        'app_release.delete' => 'حذف نسخه‌ی اپ',
        'bot.token_updated' => 'توکن ربات',
        'bot.token_removed' => 'حذف توکن ربات',
        'salon.verification' => 'تأیید / رد احراز سالن',
        'retained-data.search' => 'جست‌وجوی داده‌ی نگهداری‌شده',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function salon(): BelongsTo
    {
        return $this->belongsTo(Salon::class)->withoutGlobalScopes();
    }

    public function actionLabel(): string
    {
        return self::ACTIONS[$this->action] ?? $this->action;
    }
}
