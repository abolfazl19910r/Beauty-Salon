@props(['label'])
{{-- منوی «⋯» ردیف در پنل کارشناس (بسته‌ی ۳ ظاهری) — همان الگوی <x-admin.row-menu>: <details> بی JS هم کار می‌کند؛ اسکریپت layout
     پنل را fixed کنار دکمه می‌گذارد، فقط یکی باز، بستن با کلیک بیرون/Escape. --}}
<details {{ $attributes->class(['spec-menu']) }} data-spec-menu-root>
    <summary class="spec-menu__btn" aria-label="{{ $label }}" aria-haspopup="menu">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><circle cx="5" cy="12" r="1.8"/><circle cx="12" cy="12" r="1.8"/><circle cx="19" cy="12" r="1.8"/></svg>
    </summary>
    <div class="spec-menu__panel" role="menu" aria-label="{{ $label }}">
        {{ $slot }}
    </div>
</details>
