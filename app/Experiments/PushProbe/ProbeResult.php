<?php

namespace App\Experiments\PushProbe;

/** نتیجه‌ی یک درخواست به سرویس پوش (فقط «تحویل به سرویس»، نه رسیدن به گوشی). */
final class ProbeResult
{
    public function __construct(
        public readonly bool $ok,
        public readonly ?int $status,
        public readonly int $ms,
        public readonly string $detail,
    ) {}
}
