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
