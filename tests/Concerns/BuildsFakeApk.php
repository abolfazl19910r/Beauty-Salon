<?php

namespace Tests\Concerns;

/**
 * APK ساختگی برای تست‌های بسته‌ی ۵: یک zip با AndroidManifest.xml دودویی (AXML) که فقط عنصر <manifest> را دارد —
 * همان چیزی که App\Services\Apps\ApkManifestReader می‌خواند. string pool با UTF-16 (پیش‌فرض aapt) یا UTF-8 (aapt2)،
 * و حالت «نام ویژگی خالی + شناسه‌ی منبع» که aapt2 گاهی می‌سازد.
 */
trait BuildsFakeApk
{
    protected function fakeAxml(string $package, int $versionCode, string $versionName, bool $utf8 = false, bool $blankNames = false): string
    {
        // اندیس‌ها: 0 versionCode، 1 versionName، 2 package، 3 manifest، 4 مقدار versionName، 5 مقدار package
        $strings = [$blankNames ? '' : 'versionCode', $blankNames ? '' : 'versionName', 'package', 'manifest', $versionName, $package];
        if ($blankNames) {
            // نام خالی تکراری نباید اندیس را عوض کند؛ دومی را یکتا نگه داریم تا اندیس‌ها ثابت بمانند
            $strings[1] = ' ';
        }

        $pool = $this->stringPool($strings, $utf8);
        $resMap = pack('vvV', 0x0180, 8, 8 + 8).pack('VV', 0x0101021B, 0x0101021C);

        $attr = fn (int $name, int $raw, int $type, int $data) => pack('VVVvCCV', 0xFFFFFFFF, $name, $raw, 8, 0, $type, $data);
        $attrs = $attr(0, 0xFFFFFFFF, 0x10, $versionCode)
            .$attr(1, 4, 0x03, 4)
            .$attr(2, 5, 0x03, 5);
        $ext = pack('VVvvvvvv', 0xFFFFFFFF, 3, 20, 20, 3, 0, 0, 0);
        $element = pack('vvV', 0x0102, 16, 16 + strlen($ext) + strlen($attrs)).pack('VV', 1, 0xFFFFFFFF).$ext.$attrs;

        $body = $pool.$resMap.$element;

        return pack('vvV', 0x0003, 8, 8 + strlen($body)).$body;
    }

    /** @param  list<string>  $strings */
    private function stringPool(array $strings, bool $utf8): string
    {
        $data = '';
        $offsets = '';
        foreach ($strings as $s) {
            $offsets .= pack('V', strlen($data));
            if ($utf8) {
                $data .= chr(mb_strlen($s)).chr(strlen($s)).$s."\0";
            } else {
                $u = mb_convert_encoding($s, 'UTF-16LE', 'UTF-8');
                $data .= pack('v', strlen($u) / 2).$u."\0\0";
            }
        }
        while (strlen($data) % 4) {
            $data .= "\0";
        }
        $headerSize = 28;
        $stringsStart = $headerSize + strlen($offsets);

        return pack('vvVVVVVV', 0x0001, $headerSize, $stringsStart + strlen($data), count($strings), 0, $utf8 ? 0x100 : 0, $stringsStart, 0)
            .$offsets.$data;
    }

    /** مسیر یک فایل APK ساختگی موقت */
    protected function fakeApkFile(string $package = 'ir.mahru', int $versionCode = 8, string $versionName = '0.3.0', ?string $manifest = null): string
    {
        $path = tempnam(sys_get_temp_dir(), 'apk').'.apk';
        $zip = new \ZipArchive;
        $zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFromString('AndroidManifest.xml', $manifest ?? $this->fakeAxml($package, $versionCode, $versionName));
        $zip->addFromString('classes.dex', str_repeat("\0", 64));
        $zip->close();

        return $path;
    }

    protected function fakeApkUpload(string $package = 'ir.mahru', int $versionCode = 8, string $versionName = '0.3.0', ?string $manifest = null): \Illuminate\Http\UploadedFile
    {
        return new \Illuminate\Http\UploadedFile($this->fakeApkFile($package, $versionCode, $versionName, $manifest), 'app-release.apk', 'application/vnd.android.package-archive', null, true);
    }
}
