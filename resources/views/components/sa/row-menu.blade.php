@props(['label'])
{{-- منوی «⋯» یک ردیف (بسته‌ی ۱ ظاهری). <details> بی JS هم کار می‌کند؛ اسکریپت layout پنل را fixed کنار دکمه می‌گذارد
     تا ظرف اسکرول افقی جدول آن را نبُرد، و با کلیک بیرون/Escape/اسکرول می‌بندد. --}}
<details {{ $attributes->class(['sa-menu']) }} data-sa-menu-root>
    <summary class="sa-menu__btn" aria-label="{{ $label }}" aria-haspopup="menu">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><circle cx="5" cy="12" r="1.8"/><circle cx="12" cy="12" r="1.8"/><circle cx="19" cy="12" r="1.8"/></svg>
    </summary>
    <div class="sa-menu__panel" role="menu" aria-label="{{ $label }}">
        {{ $slot }}
    </div>
</details>
