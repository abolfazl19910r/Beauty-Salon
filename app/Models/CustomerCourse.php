<?php

namespace App\Models;

use App\Traits\BelongsToSalon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * دوره‌ی خریده‌شده‌ی یک مشتری = «پرونده» (بسته‌ی ب، ۲۰۲۶-۱۰-۱۴). همه‌ی اعداد snapshot لحظه‌ی خرید است.
 *
 * شمارش: sessions_used = جلسه‌های انجام‌شده + سوخت‌شده؛ «رزروشده» = نوبت‌های pending/confirmed همین دوره؛
 * مانده = sessions_total − sessions_used − رزروشده. با پیش‌پرداخت (تصمیم ۸) فقط به اندازه‌ی پول پرداخت‌شده جلسه رزرو می‌شود:
 * ceil(amount_paid / per_session_value)، حداقل ۱. هر تغییر شمارش زیر قفل ردیف همین دوره (CourseService).
 */
class CustomerCourse extends Model
{
    use BelongsToSalon;

    public const ACTIVE_BOOKING_STATUSES = ['pending', 'confirmed'];

    public const STATUSES = [
        'pending_payment' => 'در انتظار پرداخت',
        'active' => 'فعال',
        'completed' => 'تمام‌شده',
        'expired' => 'منقضی',
        'cancelled' => 'لغوشده',
    ];

    protected $fillable = [
        'salon_id', 'user_id', 'course_package_id', 'name', 'service_id', 'extra_service_ids', 'session_duration', 'specialist_id', 'sessions_total', 'interval_days',
        'min_interval_days', 'validity_days', 'list_price', 'discount_code', 'discount_amount', 'price_total', 'per_session_value',
        'min_prepayment', 'amount_paid', 'sessions_used', 'sessions_burned', 'status', 'purchased_at', 'expires_at', 'last_session_at',
        'next_due_at', 'photo_consent_at', 'photo_consent_by', 'sold_by', 'cancelled_at', 'cancelled_by', 'cancel_reason',
        'refunded_amount', 'notes',
    ];

    protected $casts = [
        'sessions_total' => 'integer',
        'extra_service_ids' => 'array',
        'session_duration' => 'integer',
        'interval_days' => 'integer',
        'min_interval_days' => 'integer',
        'validity_days' => 'integer',
        'list_price' => 'float',
        'discount_amount' => 'float',
        'price_total' => 'float',
        'per_session_value' => 'float',
        'min_prepayment' => 'float',
        'amount_paid' => 'float',
        'sessions_used' => 'integer',
        'sessions_burned' => 'integer',
        'refunded_amount' => 'float',
        'purchased_at' => 'datetime',
        'expires_at' => 'datetime',
        'last_session_at' => 'datetime',
        'next_due_at' => 'datetime',
        'photo_consent_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(CoursePackage::class, 'course_package_id')->withTrashed();
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(BeautyService::class, 'service_id');
    }

    public function specialist(): BelongsTo
    {
        return $this->belongsTo(Specialist::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(CustomerCoursePayment::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(CourseSessionNote::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(CourseSessionPhoto::class);
    }

    public function soldBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sold_by');
    }

    public function scopeForCustomer(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    // ─── شمارش ─────────────────────────────────────────────────────

    public function reservedCount(): int
    {
        return $this->bookings()->withoutGlobalScopes()->whereIn('status', self::ACTIVE_BOOKING_STATUSES)->count();
    }

    public function remainingSessions(?int $reserved = null): int
    {
        return max(0, $this->sessions_total - $this->sessions_used - ($reserved ?? $this->reservedCount()));
    }

    /** چند جلسه با پول پرداخت‌شده قابل استفاده است (کل = همه) */
    public function paidSessions(): int
    {
        if ($this->isFullyPaid() || $this->per_session_value <= 0) {
            return $this->sessions_total;
        }

        return min($this->sessions_total, max(1, (int) ceil(round($this->amount_paid / $this->per_session_value, 6))));
    }

    public function isFullyPaid(): bool
    {
        return $this->amount_paid + 0.5 >= $this->price_total;
    }

    public function balanceDue(): float
    {
        return max(0.0, round($this->price_total - $this->amount_paid, 2));
    }

    /** چرا الان جلسه‌ی تازه رزرو نمی‌شود؛ null = می‌شود */
    public function bookingBlocker(?int $reserved = null): ?string
    {
        $reserved ??= $this->reservedCount();

        return match (true) {
            $this->status === 'pending_payment' => 'پرداخت دوره هنوز انجام نشده است.',
            $this->status === 'completed' => 'همه‌ی جلسه‌های این دوره انجام شده است.',
            $this->status === 'cancelled' => 'این دوره لغو شده است.',
            $this->status === 'expired' || ($this->expires_at && $this->expires_at->isPast()) => 'مهلت استفاده از این دوره تمام شده است.',
            $this->remainingSessions($reserved) <= 0 => 'همه‌ی جلسه‌های باقی‌مانده رزرو شده‌اند.',
            $this->sessions_used + $reserved >= $this->paidSessions() => 'برای رزرو جلسه‌ی بعد، باقی مبلغ دوره را بپردازید.',
            default => null,
        };
    }

    /**
     * زودترین روز جلسه‌ی بعد: آخرین جلسه‌ی انجام‌شده یا رزروشده + min_interval_days (جلسه‌ی اول: امروز).
     * $exceptBookingId برای جابه‌جایی همان نوبت.
     */
    public function earliestNextSession(?int $exceptBookingId = null, ?Carbon $before = null): Carbon
    {
        $last = $this->bookings()->withoutGlobalScopes()
            ->whereIn('status', ['pending', 'confirmed', 'completed'])
            ->when($exceptBookingId, fn ($q) => $q->whereKeyNot($exceptBookingId))
            ->when($before, fn ($q) => $q->where('booking_time', '<', $before))
            ->max('booking_time');

        $from = $last ? Carbon::parse($last)->startOfDay()->addDays($this->min_interval_days) : now()->startOfDay();

        return $from->lt(now()->startOfDay()) ? now()->startOfDay() : $from;
    }

    /** خدمت‌های هر جلسه (اصلی + همراه‌ها؛ snapshot خرید) */
    public function sessionServices(): \Illuminate\Support\Collection
    {
        return CoursePackage::servicesFor((int) $this->service_id, (array) ($this->extra_service_ids ?? []), (int) $this->salon_id);
    }

    /** «لیزر صورت + زیربغل» */
    public function servicesLabel(): string
    {
        return $this->sessionServices()->pluck('name')->implode(' + ');
    }

    /** مدت هر جلسه (دقیقه) */
    public function sessionMinutes(): int
    {
        return (int) ($this->session_duration ?: $this->service?->duration ?: 30);
    }

    public function sessionLabel(int $number): string
    {
        return sprintf('جلسه‌ی %s از %s', to_persian_num((string) $number), to_persian_num((string) $this->sessions_total));
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function hasPhotoConsent(): bool
    {
        return $this->photo_consent_at !== null;
    }

    /** مبلغ پرداخت‌شده‌ی آنلاین (درگاه/کیف پول) — مبنای درآمد متخصص برای هر جلسه */
    public function onlinePaid(): float
    {
        return (float) $this->payments()->withoutGlobalScopes()->whereIn('method', CustomerCoursePayment::ONLINE_METHODS)->sum('amount');
    }

    /** سابقه‌ی درخواست‌ها و تصمیم‌های رضایت عکس (۲۰۲۶-۱۰-۲۸)، تازه‌ترین اول */
    public function photoConsents(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CoursePhotoConsent::class)->latest('id');
    }
}
