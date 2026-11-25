@props(['label'])
{{-- منوی «⋯» ردیف «نوبت‌های من» (بسته‌ی ۴ ظاهری) — همان الگوی منوی ردیف پنل مدیر/کارشناس: <details> بی JS هم کار می‌کند؛
     اسکریپت layouts/app پنل را fixed کنار دکمه می‌گذارد، فقط یکی باز، بستن با کلیک بیرون/Escape. --}}
<details {{ $attributes->class(['cu-menu']) }} data-cu-menu-root>
    <summary class="cu-menu__btn" aria-label="{{ $label }}" aria-haspopup="menu">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><circle cx="5" cy="12" r="1.8"/><circle cx="12" cy="12" r="1.8"/><circle cx="19" cy="12" r="1.8"/></svg>
    </summary>
    <div class="cu-menu__panel" role="menu" aria-label="{{ $label }}">
        {{ $slot }}
    </div>
</details>
