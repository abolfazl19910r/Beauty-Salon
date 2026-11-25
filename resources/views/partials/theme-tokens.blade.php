{{--
    ⭐ بسته‌ی «و»: بازنویسی توکن‌های --m-* با پالت یک قالب (سایت یا پنل مدیر)، برای تیره و روشن. بعد از partials/mahru-theme.
    $tokens = ['dark' => [...], 'light' => [...]] از App\Support\Themes\ThemeCatalog (بسته‌ی ۵الف: با accent2/on2)؛ گرادیان طلایی ماهرو جای خود را به دکمه‌ی تخت
    رنگ پالت می‌دهد (پیش‌نمایش تأییدشده).
--}}
@php
    $__vars = function (array $t): string {
        $out = '';
        foreach (['bg', 'bg2', 'bg3', 'card', 'tx', 'mu', 'gold', 'gold2', 'accent2'] as $k) {
            if (isset($t[$k])) {
                $out .= "--m-$k:{$t[$k]};";
            }
        }
        $out .= '--m-line:'.($t['line'] ?? 'rgba(0,0,0,.1)').';--m-line2:'.($t['line2'] ?? 'rgba(0,0,0,.2)').';';
        $out .= '--m-btn:rgb('.($t['gold'] ?? '0 0 0').');';
        $out .= '--m-tgrad:linear-gradient(135deg,rgb('.($t['gold2'] ?? '0 0 0').') 0%,rgb('.($t['gold'] ?? '0 0 0').') 100%);';
        $out .= '--m-hdr:rgb('.($t['bg'] ?? '0 0 0').' / .8);';
        $out .= '--m-on-gold:'.($t['on'] ?? '#FFFFFF').';';
        // بسته‌ی ۵الف: رنگ دوم؛ پالتی که ندارد (قالب‌های پنل مدیر) همان gold2 را می‌گیرد
        if (! isset($t['accent2'])) {
            $out .= '--m-accent2:'.($t['gold2'] ?? '0 0 0').';';
        }
        $out .= '--m-on2:'.($t['on2'] ?? $t['on'] ?? '#FFFFFF').';';

        return $out;
    };
    /*
     * بسته‌ی ۵الف: پانویس قالب‌های پولی همیشه تیره و به رنگ پالت (بوم): روشن = رنگ متن پالت، تیره = زمینه‌ی تیره‌تر؛ دکمه‌ی
     * «دریافت اپ» با رنگ روشن پالت (رنگ اصلی حالت تیره) و متن تیره‌ی همان.
     */
    $__darker = function (string $channels, float $by): string {
        $c = array_map('intval', preg_split('/\s+/', trim($channels)) ?: []);

        return implode(' ', array_map(fn ($v) => (int) round($v * (1 - $by)), $c + [0, 0, 0]));
    };
    $__foot = '--m-foot-ac:'.($tokens['dark']['gold'] ?? '0 0 0').';--m-foot-on:'.($tokens['dark']['on'] ?? '#000000').';';
@endphp
<style data-theme-palette>
:root,html[data-theme=dark]{ {!! $__vars($tokens['dark']) !!}{!! $__foot !!}--m-foot-bg:{{ $__darker($tokens['dark']['bg'] ?? '0 0 0', .35) }}; }
html[data-theme=light]{ {!! $__vars($tokens['light']) !!}{!! $__foot !!}--m-foot-bg:{{ $tokens['light']['tx'] ?? '0 0 0' }}; }
.btn-gold,.m-btn{box-shadow:0 10px 24px -14px rgb(var(--m-gold) / .65)}
.btn-gold:hover,.m-btn:hover{box-shadow:0 16px 30px -14px rgb(var(--m-gold) / .75)}
</style>
