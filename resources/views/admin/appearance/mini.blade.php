{{--
    طرح کوچک یک قالب (بسته‌ی «و»): $layout (mahru|vitrin|majalle|aram|bento)، $tokens (یک حالت: تیره یا روشن، کانال RGB).
    رنگ‌ها روی خود جعبه به متغیرهای --mm-* داده می‌شوند تا کنار هم با پالت‌های مختلف درست دیده شوند.
--}}
@php
    $mmBtn = $layout === 'mahru' ? 'linear-gradient(135deg,#F0DDA6 0%,#D8B96A 45%,#A67F2E 100%)' : 'rgb('.$tokens['gold'].')';
    $mmStyle = "--mm-bg:{$tokens['bg']};--mm-bg2:{$tokens['bg2']};--mm-bg3:{$tokens['bg3']};--mm-tx:{$tokens['tx']};--mm-mu:{$tokens['mu']};--mm-ac:{$tokens['gold']};--mm-ac2:{$tokens['gold2']};--mm-btn:{$mmBtn};";
@endphp
<div class="mm mm--{{ $layout }}" style="{{ $mmStyle }}" aria-hidden="true">
    @switch($layout)
        @case('vitrin')
            <div class="mm-v"><div><b>تیتر کنار تصویر</b><i class="mm-bar" style="width:80%"></i><span class="mm-form"><i class="mm-bar"></i><i class="mm-pill" style="width:30px"></i></span></div><span class="mm-blk"></span>
                <span class="mm-row"><i class="mm-blk mm-s"></i><i class="mm-blk mm-s"></i><i class="mm-blk mm-s"></i></span></div>
            @break
        @case('majalle')
            <div class="mm-j"><div><small>شماره‌ی تازه</small><b class="mm-serif">هنرِ آراستن</b><i class="mm-line"></i><small>۰۱ ··· ۰۲ ···</small></div><span class="mm-blk" style="border-radius:0"></span></div>
            @break
        @case('aram')
            <div class="mm-a"><span class="mm-ring"></span><b style="font-weight:300">سالن</b><i class="mm-bar" style="width:50%;opacity:.3"></i><span class="mm-row" style="justify-content:center"><i class="mm-ghost"></i><i class="mm-ghost" style="border-color:rgb(var(--mm-mu) / .4)"></i></span><i class="mm-line"></i></div>
            @break
        @case('bento')
            <div class="mm-b"><span style="grid-column:span 2;grid-row:span 2"><b>سالن</b></span><span style="background:var(--mm-btn)"></span><span><b style="color:rgb(var(--mm-ac))">۴٫۹</b></span><span class="mm-blk"></span><span></span><span style="grid-column:span 2"></span></div>
            @break
        @default
            <div class="mm-m"><i class="mm-bar" style="width:30%"></i><b style="color:rgb(var(--mm-ac2))">حسِ خاص بودن</b><i class="mm-pill"></i><span class="mm-row"><i class="mm-blk mm-s"></i><i class="mm-blk mm-s"></i><i class="mm-blk mm-s"></i></span></div>
    @endswitch
</div>
