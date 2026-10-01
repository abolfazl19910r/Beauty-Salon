<?php

namespace Tests\Unit\Support;

use App\Support\SmsParts;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SmsPartsTest extends TestCase
{
    public static function samples(): array
    {
        return [
            'empty' => ['', 1],
            'persian 70 = 1 part' => [str_repeat('س', 70), 1],
            'persian 71 = 2 parts' => [str_repeat('س', 71), 2],
            'persian 134 = 2 parts' => [str_repeat('س', 134), 2],
            'persian 135 = 3 parts' => [str_repeat('س', 135), 3],
            'emoji counts as two' => [str_repeat('س', 69).'👋', 2],
            'newline counts as one' => [str_repeat('س', 69)."\n", 1],
            'mixed persian and digits' => ['نوبت 1405/07/11 ساعت 15:30 تایید شد', 1],
            'latin 160 = 1 part' => [str_repeat('a', 160), 1],
            'latin 161 = 2 parts' => [str_repeat('a', 161), 2],
            'latin with an extended char' => [str_repeat('a', 159).'{', 2],
            'one persian char makes it unicode' => [str_repeat('a', 70).'س', 2],
        ];
    }

    #[DataProvider('samples')]
    public function test_parts_match_kavenegar_billing(string $text, int $parts): void
    {
        $this->assertSame($parts, SmsParts::count($text));
    }
}
