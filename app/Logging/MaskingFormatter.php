<?php

namespace App\Logging;

use App\Support\Logging\PersonalData;
use Monolog\Formatter\FormatterInterface;
use Monolog\LogRecord;

/**
 * خروجی formatter اصلی را (هر چه باشد: خط، JSON) پس از ساخته شدن ماسک می‌کند؛ پس پیام، context، پیام exception و
 * bindingهای SQL داخل stacktrace هم پوشش داده می‌شوند.
 */
final class MaskingFormatter implements FormatterInterface
{
    public function __construct(private readonly FormatterInterface $inner) {}

    public function inner(): FormatterInterface
    {
        return $this->inner;
    }

    public function format(LogRecord $record)
    {
        return $this->maskValue($this->inner->format($record));
    }

    public function formatBatch(array $records)
    {
        return $this->maskValue($this->inner->formatBatch($records));
    }

    private function maskValue(mixed $value): mixed
    {
        if (is_string($value)) {
            return PersonalData::mask($value);
        }

        if (is_array($value)) {
            return array_map(fn ($item) => $this->maskValue($item), $value);
        }

        return $value;
    }
}
