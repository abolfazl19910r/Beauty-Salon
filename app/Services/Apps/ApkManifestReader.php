<?php

namespace App\Services\Apps;

/**
 * خواندن package / versionCode / versionName از AndroidManifest.xml دودویی (AXML) داخل یک APK (بسته‌ی ۵).
 *
 * ابرمدیر APK را آپلود می‌کند؛ نسخه از خود فایل خوانده می‌شود تا شماره‌ی دستی اشتباه، بررسی «نسخه‌ی تازه» اپ را خراب نکند و APK اپ
 * همکار به‌جای اپ مشتری آپلود نشود. اگر خواندن نشد (APK ناشناخته)، null برمی‌گردد و فرم شماره‌ی دستی می‌خواهد.
 *
 * قالب AXML: سرآیند فایل (0x0003)، سپس chunkها: string pool ‏(0x0001، UTF-16 یا UTF-8)، resource map ‏(0x0180)، start element ‏(0x0102).
 * اولین start element همان <manifest> است؛ هر ویژگی ۲۰ بایت: ns، name (اندیس رشته)، rawValue، Res_value (size، res0، dataType، data).
 * aapt2 گاهی نام ویژگی را خالی می‌گذارد؛ برای همین شناسه‌ی منبع اندروید (resource map) هم بررسی می‌شود.
 */
final class ApkManifestReader
{
    private const ATTR_VERSION_CODE = 0x0101021B;

    private const ATTR_VERSION_NAME = 0x0101021C;

    private const TYPE_STRING = 0x03;

    /**
     * @return array{package: ?string, version_code: ?int, version_name: ?string}|null
     */
    public static function fromApk(string $path): ?array
    {
        $zip = new \ZipArchive;
        if ($zip->open($path) !== true) {
            return null;
        }
        $xml = $zip->getFromName('AndroidManifest.xml');
        $zip->close();

        return is_string($xml) ? self::fromAxml($xml) : null;
    }

    /**
     * @return array{package: ?string, version_code: ?int, version_name: ?string}|null
     */
    public static function fromAxml(string $data): ?array
    {
        try {
            return self::parse($data);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array{package: ?string, version_code: ?int, version_name: ?string}|null
     */
    private static function parse(string $data): ?array
    {
        $len = strlen($data);
        if ($len < 8 || self::u16($data, 0) !== 0x0003) {
            return null;
        }

        $strings = [];
        $resourceIds = [];
        $offset = self::u16($data, 2);

        while ($offset + 8 <= $len) {
            $type = self::u16($data, $offset);
            $headerSize = self::u16($data, $offset + 2);
            $size = self::u32($data, $offset + 4);
            if ($size < 8 || $offset + $size > $len) {
                return null;
            }

            if ($type === 0x0001) {
                $strings = self::stringPool($data, $offset);
            } elseif ($type === 0x0180) {
                for ($p = $offset + $headerSize; $p + 4 <= $offset + $size; $p += 4) {
                    $resourceIds[] = self::u32($data, $p);
                }
            } elseif ($type === 0x0102) {
                return self::manifestAttributes($data, $offset, $headerSize, $strings, $resourceIds);
            }

            $offset += $size;
        }

        return null;
    }

    /**
     * @param  list<string>  $strings
     * @param  list<int>  $resourceIds
     * @return array{package: ?string, version_code: ?int, version_name: ?string}|null
     */
    private static function manifestAttributes(string $data, int $chunk, int $headerSize, array $strings, array $resourceIds): ?array
    {
        $ext = $chunk + $headerSize;
        if (($strings[self::u32($data, $ext + 4)] ?? null) !== 'manifest') {
            return null;
        }

        $attrStart = self::u16($data, $ext + 8);
        $attrSize = self::u16($data, $ext + 10);
        $attrCount = self::u16($data, $ext + 12);
        $result = ['package' => null, 'version_code' => null, 'version_name' => null];

        for ($i = 0; $i < $attrCount; $i++) {
            $a = $ext + $attrStart + $i * $attrSize;
            if ($a + 20 > strlen($data)) {
                break;
            }
            $nameIndex = self::u32($data, $a + 4);
            $name = $strings[$nameIndex] ?? '';
            $resId = $resourceIds[$nameIndex] ?? 0;
            $raw = self::u32($data, $a + 8);
            $dataType = ord($data[$a + 15]);
            $value = self::u32($data, $a + 16);
            $string = $dataType === self::TYPE_STRING ? ($strings[$value] ?? null) : ($raw !== 0xFFFFFFFF ? ($strings[$raw] ?? null) : null);

            if ($name === 'package') {
                $result['package'] = $string;
            } elseif ($name === 'versionCode' || $resId === self::ATTR_VERSION_CODE) {
                $result['version_code'] = $dataType === self::TYPE_STRING ? (int) $string : $value;
            } elseif ($name === 'versionName' || $resId === self::ATTR_VERSION_NAME) {
                $result['version_name'] = $string;
            }
        }

        return $result;
    }

    /** @return list<string> */
    private static function stringPool(string $data, int $chunk): array
    {
        $count = self::u32($data, $chunk + 8);
        $utf8 = (self::u32($data, $chunk + 16) & 0x100) !== 0;
        $stringsStart = $chunk + self::u32($data, $chunk + 20);
        $offsets = $chunk + self::u16($data, $chunk + 2);
        $out = [];

        for ($i = 0; $i < $count; $i++) {
            $p = $stringsStart + self::u32($data, $offsets + $i * 4);
            if ($utf8) {
                // طول UTF-16 (نادیده) سپس طول بایتی؛ هر کدام ۱ یا ۲ بایت
                $p += (ord($data[$p]) & 0x80) ? 2 : 1;
                $n = ord($data[$p]);
                if ($n & 0x80) {
                    $n = (($n & 0x7F) << 8) | ord($data[$p + 1]);
                    $p++;
                }
                $out[] = substr($data, $p + 1, $n);
            } else {
                $n = self::u16($data, $p);
                if ($n & 0x8000) {
                    $n = (($n & 0x7FFF) << 16) | self::u16($data, $p + 2);
                    $p += 2;
                }
                $out[] = (string) mb_convert_encoding(substr($data, $p + 2, $n * 2), 'UTF-8', 'UTF-16LE');
            }
        }

        return $out;
    }

    private static function u16(string $d, int $o): int
    {
        return $o + 2 <= strlen($d) ? unpack('v', $d, $o)[1] : 0;
    }

    private static function u32(string $d, int $o): int
    {
        return $o + 4 <= strlen($d) ? unpack('V', $d, $o)[1] : 0;
    }
}
