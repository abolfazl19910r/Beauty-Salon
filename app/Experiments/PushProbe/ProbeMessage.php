<?php

namespace App\Experiments\PushProbe;

use Illuminate\Support\Carbon;

/**
 * یک پیام آزمایشی. زمان ارسال هم در data و هم داخل متن (برچسب [P:...]) می‌رود، چون وقتی اپ بسته است اعلان را
 * خود اندروید نشان می‌دهد و اپ آزمایشی بعداً فقط متن اعلانِ داخل سینی را می‌تواند بخواند.
 */
final class ProbeMessage
{
    public function __construct(
        public readonly string $id,
        public readonly int $seq,
        public readonly string $via,
        public readonly int $sentAtMs,
        public readonly string $label,
    ) {}

    public static function make(string $runId, int $seq, string $via, string $label): self
    {
        return new self("{$runId}-{$seq}", $seq, $via, (int) floor(microtime(true) * 1000), $label);
    }

    public function sentAt(): Carbon
    {
        return Carbon::createFromTimestampMs($this->sentAtMs)->setTimezone(config('app.timezone'));
    }

    public function title(): string
    {
        return "آزمایش پوش ماهرو #{$this->seq}";
    }

    public function body(): string
    {
        $parts = ['مسیر '.strtoupper($this->via), 'ارسال '.$this->sentAt()->format('H:i:s')];
        if ($this->label !== '') {
            $parts[] = $this->label;
        }

        return implode(' · ', $parts)." [P:{$this->id}:{$this->sentAtMs}]";
    }

    /** @return array<string, string> مقدارهای data در FCM باید رشته باشند */
    public function data(): array
    {
        return [
            'probe_id' => $this->id,
            'probe_seq' => (string) $this->seq,
            'sent_at_ms' => (string) $this->sentAtMs,
            'via' => $this->via,
            'label' => $this->label,
        ];
    }
}
