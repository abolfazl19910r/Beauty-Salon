<?php

namespace App\Models;

use App\Traits\BelongsToSalon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** یک پرداخت روی دوره‌ی مشتری (کل، پیش‌پرداخت یا باقی؛ آنلاین، کیف پول یا حضوری) */
class CustomerCoursePayment extends Model
{
    use BelongsToSalon;

    public const ONLINE_METHODS = ['gateway', 'wallet', 'wallet_gateway', 'full_discount'];

    protected $fillable = ['salon_id', 'customer_course_id', 'amount', 'wallet_amount', 'method', 'payment_transaction_id', 'recorded_by', 'reference', 'note'];

    protected $casts = ['amount' => 'float', 'wallet_amount' => 'float'];

    public function customerCourse(): BelongsTo
    {
        return $this->belongsTo(CustomerCourse::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function isOnline(): bool
    {
        return in_array($this->method, self::ONLINE_METHODS, true);
    }

    public function methodLabel(): string
    {
        return match ($this->method) {
            'gateway' => 'درگاه',
            'wallet' => 'کیف پول',
            'wallet_gateway' => 'کیف پول + درگاه',
            'in_person' => 'حضوری',
            'full_discount' => 'تخفیف کامل',
            default => $this->method,
        };
    }
}
