<?php

namespace App\Payments\Contracts;

use App\Payments\PayoutRequest;
use App\Payments\PayoutResult;

/**
 * ⭐ مرحله‌ی ۳ چند درگاه (۲۰۲۶-۰۹-۲۵) — تسویه (واریز به شبای متخصص) از حساب درگاه خودِ سالن.
 * هر درگاهی که API تسویه داره یک driver پیاده می‌کنه و در GatewayCatalog با 'payout' => true علامت می‌خوره.
 */
interface PayoutDriver
{
    public function key(): string;

    public function payout(PayoutRequest $request): PayoutResult;
}
