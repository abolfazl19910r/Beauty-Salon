{{-- زبانه‌های بخش «اعلان اپ (پوش)»: آمار و دستگاه‌ها (بسته‌ی ۷) --}}
<div class="flex gap-2 mb-5 text-sm" role="tablist">
    @foreach (['superadmin.push.index' => ['superadmin.push.index', 'آمار و نرخ رسید'], 'superadmin.push.devices.index' => ['superadmin.push.devices.*', 'دستگاه‌ها']] as $route => [$pattern, $label])
        <a href="{{ route($route) }}" role="tab" @if (request()->routeIs($pattern)) aria-current="page" @endif
           class="px-4 py-2 rounded-full font-bold"
           style="border: 1px solid var(--sa-border); {{ request()->routeIs($pattern) ? 'background: var(--sa-soft); color: var(--sa-accent);' : 'color: var(--sa-text-dim);' }}">{{ $label }}</a>
    @endforeach
</div>
