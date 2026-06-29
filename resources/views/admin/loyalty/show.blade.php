@extends('layouts.admin')
@section('title', 'نمایش پاداش')

@section('content')
    <div class="container px-6 mx-auto">

        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold flex items-center gap-2" style="color:var(--admin-text)">
                <svg class="w-6 h-6" style="color:var(--admin-accent)" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                {{ $reward->title }}
            </h1>
            <a href="{{ route('admin.loyalty.index') }}"
               class="inline-flex items-center gap-1 px-4 py-2 text-sm rounded-lg border"
               style="color:var(--admin-text-dim);background:var(--admin-surface);border-color:var(--admin-border)">
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
                بازگشت
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            {{-- Bonus details --}}
            <div class="rounded-xl p-6" style="background:var(--admin-surface);border:1px solid var(--admin-border)">
                <h2 class="text-base font-semibold mb-4" style="color:var(--admin-text)">جزئیات پاداش</h2>
                <div class="space-y-3">
                    @php
                        $rows = [
                            ['عنوان', $reward->title],
                            ['توضیحات', $reward->description ?? 'بدون توضیحات'],
                            ['امتیاز مورد نیاز', number_format($reward->required_points)],
                            ['نوع تخفیف', $reward->discount_type === 'fixed' ? 'مبلغ ثابت' : 'درصدی'],
                            ['مقدار تخفیف', number_format($reward->discount_amount) . ($reward->discount_type === 'percentage' ? ' %' : ' تومان')],
                            ['حداکثر استفاده', number_format($reward->max_uses)],
                            ['تعداد استفاده شده', number_format($reward->used_count)],
                        ];
                    @endphp
                    @foreach($rows as [$label, $val])
                        <div class="flex items-center gap-3 py-2" style="border-bottom:1px solid var(--admin-border)">
                            <span class="w-36 text-sm flex-shrink-0" style="color:var(--admin-text-dim)">{{ $label }}</span>
                            <span class="text-sm font-medium" style="color:var(--admin-text)">{{ $val }}</span>
                        </div>
                    @endforeach
                    <div class="flex items-center gap-3 py-2">
                        <span class="w-36 text-sm flex-shrink-0" style="color:var(--admin-text-dim)">وضعیت</span>
                        <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $reward->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                        {{ $reward->is_active ? 'فعال' : 'غیرفعال' }}
                    </span>
                    </div>
                </div>

                <div class="mt-5 pt-4" style="border-top:1px solid var(--admin-border)">
                    <p class="text-xs mb-1" style="color:var(--admin-text-light)">ایجاد شده: {{ $reward->created_at->format('Y/m/d H:i') }}</p>
                    <p class="text-xs" style="color:var(--admin-text-light)">بروزرسانی: {{ $reward->updated_at->format('Y/m/d H:i') }}</p>
                </div>
            </div>

            {{-- Actions --}}
            <div class="rounded-xl p-6" style="background:var(--admin-surface);border:1px solid var(--admin-border)">
                <h2 class="text-base font-semibold mb-4" style="color:var(--admin-text)">اقدامات</h2>
                <div class="space-y-3">
                    <a href="{{ route('admin.loyalty.edit', $reward) }}"
                       class="inline-flex items-center gap-2 px-4 py-2 text-sm text-white rounded-lg transition-colors"
                       style="background:var(--admin-accent)">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        ویرایش پاداش
                    </a>

                    <form action="{{ route('admin.loyalty.destroy', $reward) }}" method="POST" class="inline-block">
                        @csrf
                        @method('DELETE')
                        <button type="submit" data-confirm-delete
                                class="inline-flex items-center gap-2 px-4 py-2 text-sm text-white rounded-lg"
                                style="background:#dc2626">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                            حذف پاداش
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </div>
@endsection
