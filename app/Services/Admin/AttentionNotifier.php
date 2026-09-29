<?php

namespace App\Services\Admin;

use App\Models\PaymentTransaction;
use App\Models\Salon;
use App\Models\Specialist;
use App\Models\WithdrawalRequest;
use App\Notifications\Admin\Attention\AttentionRequiredNotification;
use Illuminate\Support\Facades\Notification;

/**
 * اعلان به مالک‌های سالن وقتی یک پرداخت (needs_attention) یا تسویه (needs_manual_check) تازه وارد «نیاز به بررسی» می‌شود.
 * هر مورد فقط یک بار: attention_notified_at با یک UPDATE شرطی (اتمی) ادعا می‌شود، پس دو اجرای هم‌زمان یا دوباره‌ی
 * payments:reconcile / ProcessWithdrawalJob دو اعلان نمی‌فرستند.
 */
class AttentionNotifier
{
    public function paymentFlagged(int $transactionId): void
    {
        $salonId = PaymentTransaction::withoutGlobalScopes()->whereKey($transactionId)->value('salon_id');

        if ($salonId && $this->claim(PaymentTransaction::withoutGlobalScopes(), $transactionId)) {
            $this->send('payment', $transactionId, (int) $salonId);
        }
    }

    public function withdrawalFlagged(int $withdrawalId): void
    {
        $specialistId = WithdrawalRequest::whereKey($withdrawalId)->value('specialist_id');
        $salonId = $specialistId ? Specialist::withoutGlobalScopes()->whereKey($specialistId)->value('salon_id') : null;

        if ($salonId && $this->claim(WithdrawalRequest::query(), $withdrawalId)) {
            $this->send('withdrawal', $withdrawalId, (int) $salonId);
        }
    }

    private function claim($query, int $id): bool
    {
        return $query->whereKey($id)->whereNull('attention_notified_at')->toBase()->update(['attention_notified_at' => now()]) === 1;
    }

    private function send(string $kind, int $recordId, int $salonId): void
    {
        $owners = Salon::find($salonId)?->admins()->wherePivot('role', 'owner')->get();

        if ($owners && $owners->isNotEmpty()) {
            Notification::send($owners, new AttentionRequiredNotification($kind, $recordId, $salonId));
        }
    }
}
