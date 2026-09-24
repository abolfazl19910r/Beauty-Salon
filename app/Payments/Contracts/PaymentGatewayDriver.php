<?php

namespace App\Payments\Contracts;

use App\Payments\GatewayStartRequest;
use App\Payments\GatewayStartResult;
use App\Payments\GatewayVerifyRequest;
use App\Payments\GatewayVerifyResult;

/**
 * ⭐ لایه‌ی چند درگاه — مرحله‌ی ۰ (۲۰۲۶-۰۹-۲۴). هر درگاه (زرین‌پال، و در مراحل بعد زیبال، آیدی‌پی،
 * نکست‌پی، پی‌پینگ، سامان، ملت، پارسیان) یک driver با همین قرارداد داره. همه‌ی مبالغ به **ریال**
 * (تبدیل تومان→ریال فقط در GatewayStartRequest::fromToman). driver هیچ‌وقت به CurrentSalon یا config
 * سراسری دست نمی‌زنه؛ credentials درگاه همون سالن از App\Payments\GatewayManager بهش داده می‌شه.
 */
interface PaymentGatewayDriver
{
    /** شناسه‌ی ثابت driver، مثل 'zarinpal' — همون مقداری که در salon_payment_gateways.driver ذخیره می‌شه. */
    public function key(): string;

    public function start(GatewayStartRequest $request): GatewayStartResult;

    public function verify(GatewayVerifyRequest $request): GatewayVerifyResult;
}
