{{--
    ⭐ بسته‌ی «و»: بازنویسی توکن‌های --m-* با پالت یک قالب (سایت یا پنل مدیر)، برای تیره و روشن. بعد از partials/mahru-theme.
    $tokens = ['dark' => [...], 'light' => [...]] از App\Support\Themes\ThemeCatalog؛ گرادیان طلایی ماهرو جای خود را به دکمه‌ی تخت
    رنگ پالت می‌دهد (پیش‌نمایش تأییدشده).
--}}
@php
    $__vars = function (array $t): string {
        $out = '';
        foreach (['bg', 'bg2', 'bg3', 'card', 'tx', 'mu', 'gold', 'gold2'] as $k) {
            if (isset($t[$k])) {
                $out .= "--m-$k:{$t[$k]};";
            }
        }
        $out .= '--m-line:'.($t['line'] ?? 'rgba(0,0,0,.1)').';--m-line2:'.($t['line2'] ?? 'rgba(0,0,0,.2)').';';
        $out .= '--m-btn:rgb('.($t['gold'] ?? '0 0 0').');';
        $out .= '--m-tgrad:linear-gradient(135deg,rgb('.($t['gold2'] ?? '0 0 0').') 0%,rgb('.($t['gold'] ?? '0 0 0').') 100%);';
        $out .= '--m-hdr:rgb('.($t['bg'] ?? '0 0 0').' / .8);';
        $out .= '--m-on-gold:'.($t['on'] ?? '#FFFFFF').';';

        return $out;
    };
@endphp
<style data-theme-palette>
:root,html[data-theme=dark]{ {!! $__vars($tokens['dark']) !!} }
html[data-theme=light]{ {!! $__vars($tokens['light']) !!} }
.btn-gold,.m-btn{box-shadow:0 10px 24px -14px rgb(var(--m-gold) / .65)}
.btn-gold:hover,.m-btn:hover{box-shadow:0 16px 30px -14px rgb(var(--m-gold) / .75)}
</style>
