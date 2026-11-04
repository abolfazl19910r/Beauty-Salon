<?php

namespace App\Support\Legal;

/**
 * کد لوگوی اینماد (تنظیمات پلتفرم ابرمدیر) — فهرست سفید سخت‌گیر (بررسی امنیتی ۲۰۲۶-۱۱-۰۵).
 *
 * اینماد می‌خواهد کد «عیناً» در صفحه بنشیند، پس همان متن بی escape چاپ می‌شود ({!! !!} در central/landing). پیش‌تر فقط «trustseal.enamad.ir
 * در متن باشد و iframe/form نباشد» سنجیده می‌شد: `<img onerror=…>` یا `<script>` همراه آدرس اینماد پذیرفته می‌شد — با رمز لو رفته‌ی ابرمدیر
 * یعنی اسکریپت روی صفحه‌ی اصلی، هم‌مبدا با پنل‌ها. حالا فقط شکل رسمی کد اینماد پذیرفته می‌شود:
 * - تگ‌ها فقط <a> و <img> (یک تصویر، یا تصویر داخل لینک)؛ هیچ متن، اسکریپت یا تگ دیگر؛
 * - صفت‌ها فقط از فهرست زیر — هیچ on*، هیچ srcset/style با url()؛
 * - href و src فقط https://trustseal.enamad.ir/… (یا زیردامنه‌ی enamad.ir)؛
 * - همان سنجش هنگام نمایش هم تکرار می‌شود (کد دست‌کاری‌شده در دیتابیس نمایش داده نمی‌شود).
 * کد رسمی اینماد (لینک + تصویر، با referrerpolicy و code) بی هیچ تغییری می‌گذرد و با CSP سخت‌گیر (بی 'unsafe-inline') هم کار می‌کند.
 */
final class EnamadBadge
{
    private const ALLOWED = [
        'a' => ['href', 'target', 'referrerpolicy', 'rel', 'id'],
        'img' => ['src', 'alt', 'style', 'referrerpolicy', 'code', 'id', 'width', 'height', 'loading', 'title'],
    ];

    /** پیام خطا برای فرم، یا null اگر کد پذیرفتنی است */
    public static function problem(?string $html): ?string
    {
        $html = trim((string) $html);
        if ($html === '') {
            return null;
        }

        if (strlen($html) > 5000 || ! str_contains($html, 'enamad.ir')) {
            return 'کد لوگو باید همان کد پنل اینماد باشد (لینک و تصویر از trustseal.enamad.ir).';
        }

        $doc = new \DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $loaded = $doc->loadHTML('<?xml encoding="utf-8"?><div id="enamad-root">'.$html.'</div>', LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $root = $loaded ? $doc->getElementById('enamad-root') : null;
        if (! $root) {
            return 'کد لوگوی اینماد خوانده نشد.';
        }

        $images = 0;
        $error = self::walk($root, $images);
        if ($error === null && $images !== 1) {
            $error = 'کد لوگوی اینماد باید دقیقاً یک تصویر اینماد داشته باشد.';
        }

        return $error;
    }

    public static function isSafe(?string $html): bool
    {
        return trim((string) $html) !== '' && self::problem($html) === null;
    }

    private static function walk(\DOMNode $parent, int &$images): ?string
    {
        foreach ($parent->childNodes as $node) {
            if ($node instanceof \DOMText) {
                if (trim($node->wholeText) !== '') {
                    return 'کد لوگوی اینماد فقط لینک و تصویر اینماد است (متن اضافه دارد).';
                }

                continue;
            }
            if (! $node instanceof \DOMElement) {
                return 'کد لوگوی اینماد فقط لینک و تصویر اینماد است.';
            }

            $tag = strtolower($node->tagName);
            if (! isset(self::ALLOWED[$tag])) {
                return "کد لوگوی اینماد فقط لینک و تصویر اینماد است (تگ «{$tag}» مجاز نیست).";
            }
            if ($tag === 'img') {
                $images++;
            }

            foreach ($node->attributes as $attribute) {
                $name = strtolower($attribute->name);
                $value = trim($attribute->value);
                if (! in_array($name, self::ALLOWED[$tag], true)) {
                    return "صفت «{$name}» در کد لوگوی اینماد مجاز نیست.";
                }
                if (in_array($name, ['href', 'src'], true) && ! self::isEnamadUrl($value)) {
                    return 'لینک و تصویر لوگو فقط از trustseal.enamad.ir (https) پذیرفته می‌شوند.';
                }
                if ($name === 'style' && preg_match('/url\s*\(|expression|@import|javascript:/i', $value)) {
                    return 'style تصویر اینماد نباید آدرس یا اسکریپت داشته باشد.';
                }
                if ($name === 'target' && ! in_array(strtolower($value), ['_blank', '_self'], true)) {
                    return 'target لینک اینماد فقط _blank است.';
                }
            }

            if ($error = self::walk($node, $images)) {
                return $error;
            }
        }

        return null;
    }

    private static function isEnamadUrl(string $url): bool
    {
        $parts = parse_url($url);
        $host = strtolower((string) ($parts['host'] ?? ''));

        return ($parts['scheme'] ?? '') === 'https'
            && ($host === 'trustseal.enamad.ir' || $host === 'enamad.ir' || str_ends_with($host, '.enamad.ir'))
            && ! isset($parts['user'], $parts['pass']);
    }
}
