<?php

namespace App\Observers;

use App\Models\Booking;
use App\Models\PaymentTransaction;
use App\Models\Review;
use App\Models\Salon;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\User;
use App\Services\SecurityLogService;
use Illuminate\Database\Eloquent\Model;

/**
 * IP و user agent روی کارهای کلیدی (بسته‌ی مجوز 5د؛ ماده‌ی ۶۶۸ آیین دادرسی کیفری، ماده‌ی ۸ قانون تجارت الکترونیکی «مبدأ و زمان»).
 *
 * observer روی مدل‌ها (نه در هر کنترلر) تا وب، اپ‌ها (/api/v1)، پنل مدیر و ابرمدیر همه یک‌جا پوشش داده شوند. کاربرِ ثبت‌شده همان
 * کسی است که کار را کرد (مشتری، مدیر سالن یا ابرمدیر)؛ مشتریِ نوبت در context می‌آید. بیرون از درخواست HTTP (صف، scheduler)
 * چیزی ثبت نمی‌شود — SecurityLogService::logAction. حذف حساب جدا در CustomerAccountDeletionService ثبت می‌شود.
 */
class KeyActionAuditObserver
{
    public function __construct(private readonly SecurityLogService $log) {}

    public function created(Model $model): void
    {
        match (true) {
            $model instanceof Booking => $this->log->logAction('booking_created', $model, [
                'customer_id' => $model->user_id, 'status' => $model->status, 'booking_time' => (string) $model->booking_time,
            ]),
            $model instanceof Review => $this->log->logAction('review_created', $model, ['booking_id' => $model->booking_id ?? null]),
            $model instanceof SupportTicket => $this->log->logAction('ticket_created', $model, ['category' => $model->category]),
            $model instanceof SupportTicketMessage => $this->log->logAction('ticket_message_created', $model, [
                'ticket_id' => $model->ticket_id, 'staff_reply' => (bool) $model->is_staff_reply,
            ]),
            $model instanceof Salon => $this->log->logAction('salon_created', $model, ['slug' => $model->slug]),
            $model instanceof PaymentTransaction => $this->log->logAction('payment_started', $model, $this->payment($model)),
            default => null,
        };
    }

    public function updated(Model $model): void
    {
        if ($model instanceof Booking) {
            if ($model->wasChanged('status') && in_array($model->status, ['cancelled', 'canceled'], true)) {
                $this->log->logAction('booking_cancelled', $model, ['customer_id' => $model->user_id, 'from' => $model->getOriginal('status')]);
            } elseif ($model->wasChanged('booking_time')) {
                $this->log->logAction('booking_rescheduled', $model, [
                    'customer_id' => $model->user_id, 'from' => (string) $model->getOriginal('booking_time'), 'to' => (string) $model->booking_time,
                ]);
            }

            return;
        }

        if ($model instanceof User) {
            // ناشناس‌سازی حذف حساب رمز و شماره را عوض می‌کند؛ آن کار رویداد account_deleted خودش را دارد
            if (str_starts_with((string) $model->phone, 'deleted-')) {
                return;
            }
            if ($model->wasChanged('password')) {
                $this->log->logAction('password_changed', $model, [], $this->actor($model));
            }
            if ($model->wasChanged('phone')) {
                $this->log->logAction('phone_changed', $model, ['from' => $model->getOriginal('phone'), 'to' => $model->phone], $this->actor($model));
            }

            return;
        }

        if ($model instanceof PaymentTransaction && $model->wasChanged('status') && in_array($model->status, ['paid', 'failed'], true)) {
            $this->log->logAction('payment_'.$model->status, $model, $this->payment($model));
        }
    }

    /** بازیابی رمز بدون ورود: خود همان کاربر */
    private function actor(User $user): int
    {
        return auth()->id() ?? $user->id;
    }

    private function payment(PaymentTransaction $tx): array
    {
        return ['purpose' => $tx->purpose, 'amount_rial' => $tx->amount_rial, 'driver' => $tx->driver, 'customer_id' => $tx->user_id];
    }
}
