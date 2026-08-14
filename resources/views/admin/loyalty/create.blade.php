@extends('layouts.admin')
@section('title', 'افزودن پاداش جدید')

@section('content')
    <div class="container px-6 mx-auto">

        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold flex items-center gap-2" style="color:var(--admin-text)">
                <svg class="w-6 h-6" style="color:var(--admin-accent)" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 4v16m8-8H4"/></svg>
                افزودن پاداش جدید
            </h1>
            <a href="{{ route('admin.loyalty.index') }}"
               class="inline-flex items-center gap-1 px-4 py-2 text-sm rounded-lg border"
               style="color:var(--admin-text-dim);background:var(--admin-surface);border-color:var(--admin-border)">
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
                بازگشت
            </a>
        </div>

        <div class="rounded-xl p-6 max-w-2xl" style="background:var(--admin-surface);border:1px solid var(--admin-border)">
            <form action="{{ route('admin.loyalty.rewards.store') }}" method="POST">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                    <div>
                        <label class="block text-sm font-medium mb-1" style="color:var(--admin-text-dim)">عنوان پاداش</label>
                        <input type="text" name="title" value="{{ old('title') }}" required
                               class="w-full rounded-lg px-3 py-2 text-sm"
                               style="border:1px solid var(--admin-border);background:var(--admin-bg);color:var(--admin-text)">
                        @error('title')<p class="text-xs mt-1" style="color:#DC2626">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1" style="color:var(--admin-text-dim)">امتیاز مورد نیاز</label>
                        <input type="number" name="required_points" value="{{ old('required_points', 0) }}" required min="1"
                               class="w-full rounded-lg px-3 py-2 text-sm"
                               style="border:1px solid var(--admin-border);background:var(--admin-bg);color:var(--admin-text)">
                        @error('required_points')<p class="text-xs mt-1" style="color:#DC2626">{{ $message }}</p>@enderror
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium mb-1" style="color:var(--admin-text-dim)">توضیحات</label>
                        <textarea name="description" rows="3"
                                  class="w-full rounded-lg px-3 py-2 text-sm"
                                  style="border:1px solid var(--admin-border);background:var(--admin-bg);color:var(--admin-text)">{{ old('description') }}</textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1" style="color:var(--admin-text-dim)">نوع تخفیف</label>
                        <select name="discount_type" id="discount_type"
                                class="w-full rounded-lg px-3 py-2 text-sm"
                                style="border:1px solid var(--admin-border);background:var(--admin-bg);color:var(--admin-text)">
                            <option value="fixed"      {{ old('discount_type') === 'fixed'      ? 'selected' : '' }}>مبلغ ثابت</option>
                            <option value="percentage" {{ old('discount_type') === 'percentage' ? 'selected' : '' }}>درصدی</option>
                        </select>
                        @error('discount_type')<p class="text-xs mt-1" style="color:#DC2626">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1" style="color:var(--admin-text-dim)">مقدار تخفیف</label>
                        <input type="number" name="discount_amount" id="discount_amount" value="{{ old('discount_amount', 0) }}" required min="1"
                               class="w-full rounded-lg px-3 py-2 text-sm"
                               style="border:1px solid var(--admin-border);background:var(--admin-bg);color:var(--admin-text)">
                        <p id="discount-hint" class="text-xs mt-1" style="color:var(--admin-text-light)"></p>
                        @error('discount_amount')<p class="text-xs mt-1" style="color:#DC2626">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1" style="color:var(--admin-text-dim)">حداکثر استفاده</label>
                        <input type="number" name="max_uses" value="{{ old('max_uses') }}" min="1" required
                               class="w-full rounded-lg px-3 py-2 text-sm"
                               style="border:1px solid var(--admin-border);background:var(--admin-bg);color:var(--admin-text)">
                        @error('max_uses')<p class="text-xs mt-1" style="color:#DC2626">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex items-center gap-2 mt-2">
                        <input type="checkbox" name="is_active" id="is_active" {{ old('is_active', true) ? 'checked' : '' }}
                        class="rounded" style="accent-color:var(--admin-accent)">
                        <label for="is_active" class="text-sm" style="color:var(--admin-text)">پاداش فعال باشد</label>
                    </div>
                </div>

                <div class="mt-6 pt-4" style="border-top:1px solid var(--admin-border)">
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-5 py-2 text-sm font-medium text-white rounded-lg transition-colors"
                            style="background:var(--admin-accent)"
                            onmouseover="this.style.background='var(--admin-accent-hover)'"
                            onmouseout="this.style.background='var(--admin-accent)'">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                        ذخیره پاداش
                    </button>
                </div>
            </form>
        </div>

    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            var sel = document.getElementById('discount_type');
            var hint = document.getElementById('discount-hint');
            function updateHint() {
                hint.textContent = sel.value === 'percentage' ? 'مقدار باید بین ۱ تا ۱۰۰ باشد' : 'مقدار به تومان وارد شود';
            }
            if (sel) { updateHint(); sel.addEventListener('change', updateHint); }
        })();
    </script>
@endpush
