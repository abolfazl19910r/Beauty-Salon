{{-- خطای اعتبارسنجی فرم در صفحه‌های دوره و کمپین پنل مدیر؛ پیام‌های success/error خود layouts.admin نشان می‌دهد (این‌جا تکرار نشود). styleها: CourseUi::admin() --}}
@if ($errors->any() && ! session('error'))
    <div class="mb-4 p-3 rounded-lg text-sm" style="background:rgb(var(--m-bad) / .14); color:rgb(var(--m-bad));">{{ $errors->first() }}</div>
@endif
