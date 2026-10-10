<?php

namespace App\Notifications\Admin\Wallet;

use App\Models\Specialist;
use App\Models\SpecialistWallet;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * ⭐ بسته‌ی ۲ اپلیکیشن (۲۰۲۶-۱۰-۱۰): متخصص از اپ همکار شبا را عوض کرد → اعلان داخلی به مالک سالن. شبای تازه
 * تأییدنشده است و تا مالک تأیید نکند تسویه‌ی خودکار ندارد؛ این اعلان مالک را به صفحه‌ی تأیید می‌برد و اگر تغییر کار
 * خود متخصص نباشد (گوشی گم‌شده) زود دیده می‌شود. هشدار امنیتی است، پس از تنظیمات اعلان سالن خاموش نمی‌شود.
 */
class SpecialistIbanChangedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Specialist $specialist,
        private readonly SpecialistWallet $wallet,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'specialist_iban_changed',
            'salon_id' => $this->specialist->salon_id,
            'specialist_id' => $this->specialist->id,
            'wallet_id' => $this->wallet->id,
            'message' => "شبای {$this->specialist->name} از اپ تغییر کرد و منتظر تأیید شماست.",
            'link' => route('admin.wallet.show', $this->wallet->id, false),
        ];
    }
}
