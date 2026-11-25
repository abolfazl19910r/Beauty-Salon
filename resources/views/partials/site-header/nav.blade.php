{{-- بسته‌ی ۵الف: پیوندهای منوی سربرگ سایت سالن (مشترک همه‌ی چیدمان‌ها؛ متغیرها از layouts/app) --}}
@if ($__navCategories->isEmpty())
    <a href="{{ route('services.index') }}" class="nav-link">خدمات</a>
@else
    <div class="relative" data-nav-categories>
        <button type="button" class="nav-link inline-flex items-center gap-1" aria-haspopup="true" aria-expanded="false" data-nav-categories-btn>
            خدمات
            <svg class="w-3.5 h-3.5 transition-transform" data-nav-categories-chevron viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
        </button>
        <div class="hidden absolute top-full right-0 mt-3 w-[min(30rem,80vw)] rounded-2xl border border-m-gold/20 bg-m-card shadow-2xl p-3" data-nav-categories-panel>
            <a href="{{ route('services.index') }}" class="flex items-center justify-between rounded-xl px-3 py-2.5 font-semibold text-m-gold2 hover:bg-m-gold/10">
                همه‌ی خدمات
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
            </a>
            <div class="my-2 border-t border-m-gold/15"></div>
            <div class="grid grid-cols-2 gap-1 max-h-[60vh] overflow-y-auto">
                @foreach ($__navCategories as $__navCategory)
                    <a href="{{ route('services.index', ['category' => $__navCategory->id]) }}"
                       class="flex items-center justify-between gap-2 rounded-xl px-3 py-2 text-m-tx/80 hover:bg-m-gold/10 hover:text-m-gold2 {{ (string) request('category') === (string) $__navCategory->id ? 'bg-m-gold/10 text-m-gold2' : '' }}">
                        <span class="truncate">{{ $__navCategory->name }}</span>
                        <span class="text-xs text-m-tx/45 persian-number shrink-0">{{ to_persian_num((string) $__navCategory->services_count) }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
@endif
<a href="{{ route('specialists.search') }}" class="nav-link">کارشناسان</a>
{{-- دوره‌های درمانی (بسته‌ی ب): فقط با امکان پلن سالن --}}
@if ($__coursesOn)
    <a href="{{ route('courses.index') }}" class="nav-link">دوره‌ها</a>
@endif
<a href="{{ route('blog.index') }}" class="nav-link">وبلاگ</a>
