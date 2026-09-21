@extends('layouts.admin')
@section('title', 'تیکت پشتیبانی جدید')

@section('content')
    <div class="fade-in max-w-2xl">
        <h1 class="text-xl font-bold mb-5" style="color:var(--admin-text);">تیکت پشتیبانی جدید</h1>

        @if($errors->any())
            <div class="mb-4 px-4 py-3 rounded-lg text-sm" style="background-color: rgba(220,38,38,0.1); color: #DC2626;">
                <ul class="list-disc pr-4">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.support-tickets.store') }}" method="POST"
              class="rounded-xl p-6 space-y-4" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
            @csrf

            <div>
                <label class="block text-sm mb-1" style="color:var(--admin-text);">عنوان</label>
                <input type="text" name="title" value="{{ old('title') }}" required maxlength="255"
                       class="w-full px-3 py-2 rounded-lg" style="border:1px solid var(--admin-border); color:var(--admin-text);">
            </div>

            <div>
                <label class="block text-sm mb-1" style="color:var(--admin-text);">توضیحات</label>
                <textarea name="description" rows="6" required maxlength="5000"
                          class="w-full px-3 py-2 rounded-lg" style="border:1px solid var(--admin-border); color:var(--admin-text);">{{ old('description') }}</textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm mb-1" style="color:var(--admin-text);">دسته‌بندی</label>
                    <select name="category" required class="w-full px-3 py-2 rounded-lg" style="border:1px solid var(--admin-border); color:var(--admin-text);">
                        <option value="booking" @selected(old('category') === 'booking')>رزرو و نوبت‌دهی</option>
                        <option value="payment" @selected(old('category') === 'payment')>پرداخت</option>
                        <option value="service" @selected(old('category') === 'service')>خدمات سالن</option>
                        <option value="technical" @selected(old('category') === 'technical')>فنی</option>
                        <option value="other" @selected(old('category') === 'other')>سایر</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm mb-1" style="color:var(--admin-text);">اولویت</label>
                    <select name="priority" class="w-full px-3 py-2 rounded-lg" style="border:1px solid var(--admin-border); color:var(--admin-text);">
                        <option value="low" @selected(old('priority') === 'low')>کم</option>
                        <option value="medium" @selected(old('priority', 'medium') === 'medium')>متوسط</option>
                        <option value="high" @selected(old('priority') === 'high')>زیاد</option>
                        <option value="urgent" @selected(old('priority') === 'urgent')>فوری</option>
                    </select>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <a href="{{ route('admin.support-tickets.index') }}"
                   class="px-4 py-2 rounded-lg text-sm" style="border:1px solid var(--admin-border); color:var(--admin-text-dim);">انصراف</a>
                <button type="submit" class="px-4 py-2 rounded-lg text-sm text-white" style="background-color: var(--admin-accent);">
                    ثبت تیکت
                </button>
            </div>
        </form>
    </div>
@endsection
