@extends('layouts.admin')
@section('title', 'مدیریت امتیازات')

@section('content')
    <div class="container px-6 mx-auto">

        {{-- Header --}}
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold flex items-center gap-2" style="color:var(--admin-text)">
                <svg class="w-6 h-6" style="color:var(--admin-accent)" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                مدیریت امتیازات
            </h1>
            <a href="{{ route('admin.dashboard') }}"
               class="inline-flex items-center gap-1 px-4 py-2 text-sm rounded-lg border transition-colors"
               style="color:var(--admin-text-dim);background:var(--admin-surface);border-color:var(--admin-border)">
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
                بازگشت
            </a>
        </div>

        {{-- Statistics cards --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            @php
                $statCards = [
                    ['icon'=>'M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z','bg'=>'#faf5ff','ic'=>'#7c3aed','label'=>'کل امتیازات فعال','value'=>number_format($totalActivePoints)],
                    ['icon'=>'M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75','bg'=>'#eff6ff','ic'=>'#2563eb','label'=>'کاربران دارای امتیاز','value'=>number_format($totalPointUsers)],
                    ['icon'=>'M23 6 13.5 15.5 8.5 10.5 1 18M17 6h6v6','bg'=>'#f0fdf4','ic'=>'#16a34a','label'=>'میانگین امتیاز','value'=>number_format($averageUserPoints)],
                    ['icon'=>'M1 4h22v16H1zM1 10h22','bg'=>'#fffbeb','ic'=>'#d97706','label'=>'پاداش‌های استفاده شده','value'=>number_format($totalRedeemedRewards)],
                ];
            @endphp
            @foreach($statCards as $card)
                <div class="rounded-xl p-5 flex items-center gap-4" style="background:var(--admin-surface);border:1px solid var(--admin-border)">
                    <div class="p-3 rounded-lg flex-shrink-0" style="background:{{ $card['bg'] }}">
                        <svg class="w-6 h-6" style="color:{{ $card['ic'] }}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="{{ $card['icon'] }}"/></svg>
                    </div>
                    <div>
                        <p class="text-xs mb-1" style="color:var(--admin-text-dim)">{{ $card['label'] }}</p>
                        <p class="text-2xl font-bold" style="color:var(--admin-text)">{{ $card['value'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        <h2 class="text-lg font-bold mb-4" style="color:var(--admin-text)">مدیریت برنامه‌ی وفاداری</h2>

        {{-- Add new bonus — regular HTML form, no AJAX/fetch --}}
        <div class="rounded-xl p-6 mb-6" style="background:var(--admin-surface);border:1px solid var(--admin-border)">
            <h3 class="text-base font-semibold mb-4" style="color:var(--admin-text)">افزودن پاداش جدید</h3>

            <form action="{{ route('admin.loyalty.rewards.store') }}" method="POST">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-medium mb-1" style="color:var(--admin-text-dim)">عنوان</label>
                        <input type="text" name="title" value="{{ old('title') }}" required
                               class="w-full rounded-lg px-3 py-2 text-sm"
                               style="border:1px solid var(--admin-border);background:var(--admin-bg);color:var(--admin-text)">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1" style="color:var(--admin-text-dim)">امتیاز مورد نیاز</label>
                        <input type="number" name="required_points" value="{{ old('required_points', 0) }}" required min="1"
                               class="w-full rounded-lg px-3 py-2 text-sm"
                               style="border:1px solid var(--admin-border);background:var(--admin-bg);color:var(--admin-text)">
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
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1" style="color:var(--admin-text-dim)">مقدار تخفیف</label>
                        <input type="number" name="discount_amount" id="discount_amount" value="{{ old('discount_amount', 0) }}" required min="1"
                               class="w-full rounded-lg px-3 py-2 text-sm"
                               style="border:1px solid var(--admin-border);background:var(--admin-bg);color:var(--admin-text)">
                        <p id="discount-hint" class="text-xs mt-1" style="color:var(--admin-text-light)"></p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1" style="color:var(--admin-text-dim)">حداکثر استفاده</label>
                        <input type="number" name="max_uses" value="{{ old('max_uses', 1) }}" required min="1"
                               class="w-full rounded-lg px-3 py-2 text-sm"
                               style="border:1px solid var(--admin-border);background:var(--admin-bg);color:var(--admin-text)">
                    </div>

                    <div class="flex items-center gap-2 mt-2">
                        <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                        class="rounded" style="accent-color:var(--admin-accent)">
                        <label for="is_active" class="text-sm" style="color:var(--admin-text)">فعال</label>
                    </div>
                </div>

                <div class="mt-6 pt-4" style="border-top:1px solid var(--admin-border)">
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-5 py-2 text-sm font-medium text-white rounded-lg transition-colors"
                            style="background:var(--admin-accent)"
                            onmouseover="this.style.background='var(--admin-accent-hover)'"
                            onmouseout="this.style.background='var(--admin-accent)'">
                        افزودن پاداش
                    </button>
                </div>
            </form>
        </div>

        {{-- Bonus List — Direct Server-Side Rendering --}}
        <div class="rounded-xl overflow-hidden" style="background:var(--admin-surface);border:1px solid var(--admin-border)">
            <div class="p-6" style="border-bottom:1px solid var(--admin-border)">
                <h3 class="text-base font-semibold" style="color:var(--admin-text)">لیست پاداش‌ها</h3>
            </div>

            @if($rewards->isEmpty())
                <div class="p-10 text-center text-sm" style="color:var(--admin-text-dim)">
                    هیچ پاداشی یافت نشد
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-right">
                        <thead>
                        <tr style="border-bottom:1px solid var(--admin-border)">
                            <th class="p-4 font-medium" style="color:var(--admin-text-dim)">عنوان</th>
                            <th class="p-4 font-medium" style="color:var(--admin-text-dim)">امتیاز مورد نیاز</th>
                            <th class="p-4 font-medium" style="color:var(--admin-text-dim)">مقدار تخفیف</th>
                            <th class="p-4 font-medium" style="color:var(--admin-text-dim)">استفاده</th>
                            <th class="p-4 font-medium" style="color:var(--admin-text-dim)">وضعیت</th>
                            <th class="p-4 font-medium" style="color:var(--admin-text-dim)">عملیات</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($rewards as $reward)
                            <tr style="border-bottom:1px solid var(--admin-border)">
                                <td class="p-4" style="color:var(--admin-text)">{{ $reward->title }}</td>
                                <td class="p-4" style="color:var(--admin-text)">{{ number_format($reward->required_points) }}</td>
                                <td class="p-4" style="color:var(--admin-text)">
                                    {{ number_format($reward->discount_amount) }}{{ $reward->discount_type === 'percentage' ? ' %' : ' تومان' }}
                                </td>
                                <td class="p-4" style="color:var(--admin-text)">
                                    {{ number_format($reward->used_count) }} / {{ number_format($reward->max_uses) }}
                                </td>
                                <td class="p-4">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $reward->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                            {{ $reward->is_active ? 'فعال' : 'غیرفعال' }}
                                        </span>
                                </td>
                                <td class="p-4">
                                    <div class="flex items-center gap-3">
                                        <a href="{{ route('admin.loyalty.rewards.show', $reward) }}"
                                           class="text-xs font-medium" style="color:var(--admin-text-dim)">نمایش</a>
                                        <a href="{{ route('admin.loyalty.rewards.edit', $reward) }}"
                                           class="text-xs font-medium" style="color:var(--admin-accent)">ویرایش</a>
                                        <form action="{{ route('admin.loyalty.rewards.destroy', $reward) }}" method="POST" class="inline-block">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" data-confirm-delete
                                                    class="text-xs font-medium" style="color:#dc2626">
                                                حذف
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            var sel = document.getElementById('discount_type');
            var hint = document.getElementById('discount-hint');
            function updateHint() {
                if (!sel || !hint) return;
                hint.textContent = sel.value === 'percentage' ? 'مقدار باید بین ۱ تا ۱۰۰ باشد' : 'مقدار به تومان وارد شود';
            }
            if (sel) { updateHint(); sel.addEventListener('change', updateHint); }
        })();
    </script>
@endpush
