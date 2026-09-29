<?php

namespace App\Notifications\Admin\Attention;

use App\Models\PaymentTransaction;
use App\Models\Salon;
use App\Models\Specialist;
use App\Models\WithdrawalRequest;
use App\Payments\GatewayCatalog;
use App\Services\Notification\NotificationSettingService;
use App\Services\SMSService;
use App\Support\Notifications\NotificationEvents;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * یک پرداخت یا تسویه که خودکارسازی به نتیجه نرساند و دخالت مالک لازم دارد (AttentionNotifier).
 * تنظیمات کانال‌ها از سالنِ خودِ مورد خوانده می‌شود (نه از گیرنده — مالکی که چند سالن دارد).
 */
class AttentionRequiredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $kind,
        public readonly int $recordId,
        public readonly int $salonId,
    ) {}

    public function via(object $notifiable): array
    {
        $event = $this->kind === 'payment' ? NotificationEvents::PAYMENT_ATTENTION_ADMIN : NotificationEvents::WITHDRAWAL_ATTENTION_ADMIN;

        return app(NotificationSettingService::class)->channels($event, ['database', 'sms'], $this->salonId);
    }

    public function toArray(object $notifiable): array
    {
        if ($this->kind === 'payment') {
            $tx = PaymentTransaction::withoutGlobalScopes()->find($this->recordId);

            return [
                'type' => 'payment_attention_admin',
                'transaction_id' => $this->recordId,
                'salon_id' => $this->salonId,
                'message' => sprintf(
                    'پرداخت %s تومان (%s، تراکنش #%s) به بررسی شما نیاز دارد. نتیجه‌ی آن در درگاه روشن نشد.',
                    number_format($this->paymentToman($tx)), $this->driverLabel($tx), $this->recordId
                ),
                'link' => route('admin.payment-attention.index', [], false),
            ];
        }

        $withdrawal = WithdrawalRequest::find($this->recordId);

        return [
            'type' => 'withdrawal_attention_admin',
            'withdrawal_request_id' => $this->recordId,
            'salon_id' => $this->salonId,
            'message' => sprintf(
                'نتیجه‌ی واریز خودکار %s تومان به %s نامعلوم است (برداشت #%s). قبل از تأیید یا رد، در پنل درگاه بررسی کنید.',
                number_format((float) ($withdrawal->net_amount ?? 0)), $this->specialistName($withdrawal), $this->recordId
            ),
            'link' => route('admin.wallet.withdrawals.show', $this->recordId, false),
        ];
    }

    public function toSms(object $notifiable): bool
    {
        return (new SMSService)->send($notifiable->phone, $this->smsText());
    }

    public function smsText(): string
    {
        $salonName = Salon::whereKey($this->salonId)->value('name') ?? '';

        if ($this->kind === 'payment') {
            $tx = PaymentTransaction::withoutGlobalScopes()->find($this->recordId);

            return sprintf(
                "⚠️ پرداخت نیازمند بررسی\nسالن: %s\nمبلغ: %s تومان\nدرگاه: %s\nتراکنش #%s\nنتیجه در درگاه روشن نشد؛ از پنل مدیریت ← «پرداخت‌های نیازمند بررسی» رسیدگی کنید.",
                $salonName, number_format($this->paymentToman($tx)), $this->driverLabel($tx), $this->recordId
            );
        }

        $withdrawal = WithdrawalRequest::find($this->recordId);

        return sprintf(
            "⚠️ تسویه‌ی نیازمند بررسی\nسالن: %s\nمتخصص: %s\nمبلغ: %s تومان\nبرداشت #%s\nنتیجه‌ی واریز خودکار نامعلوم است؛ قبل از تأیید یا رد، در پنل درگاه بررسی کنید.",
            $salonName, $this->specialistName($withdrawal), number_format((float) ($withdrawal->net_amount ?? 0)), $this->recordId
        );
    }

    private function paymentToman(?PaymentTransaction $tx): int
    {
        return intdiv((int) ($tx->amount_rial ?? 0), 10);
    }

    private function driverLabel(?PaymentTransaction $tx): string
    {
        return $tx ? GatewayCatalog::label($tx->driver) : 'نامشخص';
    }

    private function specialistName(?WithdrawalRequest $withdrawal): string
    {
        return ($withdrawal ? Specialist::withoutGlobalScopes()->whereKey($withdrawal->specialist_id)->value('name') : null) ?? 'نامشخص';
    }
}
