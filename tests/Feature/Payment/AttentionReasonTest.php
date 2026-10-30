<?php

namespace Tests\Feature\Payment;

use App\Models\PaymentTransaction;
use Tests\TestCase;

/**
 * ⭐ آزمون دستی 5ز (۲۰۲۶-۱۰-۳۱، بند ۸): خرید بسته‌ی پیامک بی بازگشت، که reconcile آن را از زرین‌پال پرسید و «پرداخت شده» آمد، در صفحه‌ی
 * ابرمدیر با متن «پاسخ تایید درگاه نرسید و ۲۴ ساعت…» دیده شد — غلط. هر سه پرچم تازه‌ی 5ز متن خودشان را دارند.
 */
class AttentionReasonTest extends TestCase
{
    private function tx(array $response, string $status = 'failed', ?string $refId = null): PaymentTransaction
    {
        return new PaymentTransaction(['driver' => 'zarinpal', 'status' => $status, 'verify_response' => $response, 'ref_id' => $refId]);
    }

    public function test_each_5z_flag_explains_itself(): void
    {
        $paid = $this->tx(['recovery' => 'verified_after_abandon'], refId: '777003')->attentionReason();
        $this->assertStringContainsString('زرین‌پال پرداخت را تأیید کرد', $paid);
        $this->assertStringContainsString('777003', $paid);
        $this->assertStringNotContainsString('۲۴ ساعت', $paid);

        $this->assertStringContainsString('-52', $this->tx(['recovery' => 'needs_attention', 'needs_attention' => true, 'errors' => ['code' => -52]])->attentionReason());
        $this->assertStringContainsString('-50', $this->tx(['needs_attention' => true, 'errors' => ['code' => -50]])->attentionReason());
        $this->assertStringContainsString('۲۴ ساعت', $this->tx(['recovery' => 'gave_up'], 'expired')->attentionReason());
    }
}
