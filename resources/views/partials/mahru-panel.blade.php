{{--
    ⭐ بسته‌ی ه (۲۰۲۶-۱۰-۱۵): پنل‌های مدیر و متخصص روی سیستم طراحی ماهرو. بعد از partials/mahru-theme
    (توکن‌ها، فونت محلی، تم تیره/روشن) می‌آید و متغیرهای قدیمی پنل‌ها را به توکن‌ها وصل می‌کند تا ۱۰۰+ نما
    بدون بازنویسی دو تم بگیرند. پنل ابرمدیر بیرون از دامنه است.
--}}
<style>
:root{
    --m-on-solid:21 17 14;
    /* پنل مدیر (قبلاً خاکستری روشن ثابت) */
    --admin-bg:rgb(var(--m-bg));--admin-surface:rgb(var(--m-bg2));--admin-card:rgb(var(--m-bg2));
    --admin-border:var(--m-line2);--admin-text:rgb(var(--m-tx));--admin-text-dim:rgb(var(--m-mu));
    --admin-text-light:rgb(var(--m-mu) / .75);--admin-accent:rgb(var(--m-gold));--admin-accent-hover:rgb(var(--m-gold2));
    --admin-accent-light:rgb(var(--m-gold) / .1);
    /* پنل متخصص (قبلاً بنفش تیره‌ی ثابت) */
    --specialist-bg:rgb(var(--m-bg));--specialist-surface:rgb(var(--m-bg2));--specialist-border:var(--m-line2);
    --specialist-plum-light:rgb(var(--m-gold2));--specialist-plum-mid:rgb(var(--m-gold));--specialist-plum-muted:rgb(var(--m-gold) / .8);
    --specialist-text:rgb(var(--m-tx));--specialist-text-dim:rgb(var(--m-mu));--specialist-inactive:rgb(var(--m-mu) / .6);
}
html[data-theme=light]{--m-on-solid:255 255 255}

.font-vazir,.font-serif-fa{font-family:"Mahru","Vazirmatn",system-ui,sans-serif}
.font-serif-fa{font-weight:800}

/* منوی کناری: گزینه‌ی فعال طلایی */
.sidebar-active{
    background:linear-gradient(90deg,rgb(var(--m-gold) / .05) 0%,rgb(var(--m-gold) / .16) 100%)!important;
    border-right:3px solid rgb(var(--m-gold))!important;color:rgb(var(--m-gold2))!important;
}

/* هر چیزی که با رنگ اصلی پنل رنگ شده بود ← دکمه‌ی طلایی لندینگ با متن تیره‌ی ثابت */
.admin-btn-primary,.specialist-cta,
[style*="background: var(--admin-accent)"],[style*="background:var(--admin-accent)"],
[style*="background-color: var(--admin-accent)"],[style*="background-color:var(--admin-accent)"],
[style*="background: var(--admin-accent-hover)"]{
    background:var(--m-btn)!important;color:var(--m-on-gold)!important;
}
a.admin-btn-primary,button.admin-btn-primary,
a[style*="var(--admin-accent)"][style*="background"],button[style*="var(--admin-accent)"][style*="background"]{border-radius:999px!important}
.admin-btn-primary:hover{filter:saturate(1.1) brightness(1.03)}

/* کارت‌ها */
.admin-card,.specialist-card{background:rgb(var(--m-bg2));border:1px solid var(--m-line);border-radius:18px}
.hover-shadow-lg{transition:box-shadow .3s,transform .3s}
.hover-shadow-lg:hover{box-shadow:var(--m-sh-soft);transform:translateY(-2px)}
.specialist-badge{background:rgb(var(--m-gold) / .14);color:rgb(var(--m-gold2));border-radius:999px}

/* ورودی‌ها: پلاگین forms پس‌زمینه‌ی سفید ثابت می‌دهد */
.m-panel input:not([type=checkbox]):not([type=radio]):not([type=range]):not([type=color]):not([type=file]),
.m-panel select,.m-panel textarea{background-color:rgb(var(--m-bg));border-color:var(--m-line2);color:rgb(var(--m-tx))}
.m-panel input:focus,.m-panel select:focus,.m-panel textarea:focus{border-color:rgb(var(--m-gold));box-shadow:0 0 0 3px rgb(var(--m-gold) / .2);outline:none}
.m-panel input[type=checkbox],.m-panel input[type=radio]{accent-color:rgb(var(--m-gold));color:rgb(var(--m-gold))}
.m-panel table thead{background:rgb(var(--m-tx) / .03)}
.m-panel input[type=file]{color:rgb(var(--m-mu))}
.m-panel input[type=file]::file-selector-button{margin-inline-end:10px;border:1px solid var(--m-line2);border-radius:999px;padding:6px 14px;background:rgb(var(--m-tx) / .06);color:rgb(var(--m-tx));font:inherit;cursor:pointer}

/* SweetAlert2 روی توکن‌ها */
.swal2-popup{background:rgb(var(--m-bg2))!important;color:rgb(var(--m-tx))!important;border:1px solid var(--m-line);border-radius:22px!important}
.swal2-title,.swal2-html-container{color:rgb(var(--m-tx))!important}
.swal2-confirm{background:var(--m-btn)!important;color:var(--m-on-gold)!important;border-radius:999px!important}
.swal2-cancel{background:rgb(var(--m-tx) / .08)!important;color:rgb(var(--m-tx))!important;border-radius:999px!important}
</style>
<script>
// رنگ توکن برای نمودارها (ApexCharts رنگ CSS متغیر نمی‌فهمد): mToken('gold') → "rgb(216,185,106)"
window.mToken = function (name, alpha) {
    var v = getComputedStyle(document.documentElement).getPropertyValue('--m-' + name).trim().split(/\s+/).join(',');
    return alpha === undefined ? 'rgb(' + v + ')' : 'rgba(' + v + ',' + alpha + ')';
};
</script>
