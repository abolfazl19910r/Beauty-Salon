{{--
    دکمه‌ی فارسی انتخاب فایل به‌جای «Choose File / No file chosen» بومی مرورگر (بسته‌ی ۰، ۲۰۲۶-۱۱-۲۴). خود <input type="file">
    (با همه‌ی ویژگی‌های داده‌شده: name، id، accept، required، capture…) درون label می‌ماند و فقط دیده نمی‌شود، پس فرم، JS صفحه
    و فوکوس صفحه‌کلید مثل قبل کار می‌کنند؛ partials/ui-handlers (data-ui-file) نام فایل را نشان می‌دهد.
--}}
@props(['button' => 'انتخاب فایل', 'empty' => 'فایلی انتخاب نشده', 'wrapClass' => '', 'buttonStyle' => 'border:1px solid currentColor;'])
<label class="text-sm {{ $wrapClass }}" data-ui-file style="position:relative;display:inline-flex;flex-wrap:wrap;align-items:center;gap:.5rem;cursor:pointer">
    <input type="file" {{ $attributes->except(['class', 'style']) }}
           style="position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0;opacity:0">
    <span style="display:inline-flex;align-items:center;gap:.3rem;padding:.35rem .8rem;border-radius:.5rem;font-weight:500;{{ $buttonStyle }}">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
        {{ $button }}
    </span>
    <span data-ui-file-name data-empty="{{ $empty }}" style="opacity:.75">{{ $empty }}</span>
</label>
