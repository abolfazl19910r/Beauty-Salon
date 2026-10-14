{{-- پیام‌های موفقیت/خطای صفحه‌های دوره‌ی پنل مدیر (بسته‌ی ب)؛ styleها: CourseUi::admin() --}}
@if (session('success'))
    <div class="mb-4 p-3 rounded-lg text-sm" style="background:var(--admin-accent-light); color:var(--admin-accent);">{{ session('success') }}</div>
@endif
@if (session('error') || $errors->any())
    <div class="mb-4 p-3 rounded-lg text-sm" style="background:#fee2e2; color:#b91c1c;">{{ session('error') ?? $errors->first() }}</div>
@endif
