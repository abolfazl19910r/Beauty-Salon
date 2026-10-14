{{-- دکمه‌ی تم تیره/روشن (همان کلید «mahru-theme» لندینگ؛ انتخاب روی همه‌ی صفحه‌ها می‌ماند) --}}
<button type="button" class="m-theme-btn {{ $class ?? '' }}" data-m-theme-toggle aria-label="تغییر تم تیره و روشن" title="تم تیره / روشن">
    <svg class="m-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="12" cy="12" r="4.2"/><path d="M12 2.5v2.2M12 19.3v2.2M4.6 4.6l1.6 1.6M17.8 17.8l1.6 1.6M2.5 12h2.2M19.3 12h2.2M4.6 19.4l1.6-1.6M17.8 6.2l1.6-1.6"/></svg>
    <svg class="m-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20.5 14.2A8.5 8.5 0 0 1 9.8 3.5a8.5 8.5 0 1 0 10.7 10.7Z"/></svg>
</button>
@once
<script>
document.addEventListener('click', function (e) {
    var btn = e.target.closest && e.target.closest('[data-m-theme-toggle]');
    if (!btn) return;
    var root = document.documentElement;
    root.dataset.theme = root.dataset.theme === 'light' ? 'dark' : 'light';
    try { localStorage.setItem('mahru-theme', root.dataset.theme); } catch (err) {}
});
</script>
@endonce
