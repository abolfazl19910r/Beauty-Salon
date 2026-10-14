@extends('layouts.admin')
@section('title', 'فروش حضوری دوره')

@section('content')
    <div class="fade-in" style="max-width: 40rem;">
        @php extract(\App\Support\Courses\CourseUi::admin()) @endphp
        @include('admin.courses._ui')
        <h1 class="text-xl font-bold mb-2" style="color:var(--admin-text);">فروش حضوری دوره</h1>
        <p class="text-sm mb-5" style="{{ $dim }}">پول در سالن گرفته می‌شود و از پلتفرم عبور نمی‌کند. با پیش‌پرداخت، باقی را بعداً در صفحه‌ی همان دوره ثبت کنید.</p>

        @if ($packages->isEmpty())
            <div class="rounded-xl p-5 text-sm" style="{{ $box }} {{ $dim }}">اول در «بسته‌ها و تنظیمات» یک بسته‌ی فعال بسازید.</div>
        @else
            <form method="POST" action="{{ route('admin.courses.sell.store') }}" class="rounded-xl p-5 space-y-4" style="{{ $box }}" novalidate>
                @csrf
                <div>
                    <label class="block text-sm font-medium mb-1" for="package_id">بسته</label>
                    <select id="package_id" name="package_id" class="{{ $input }}" style="{{ $inputStyle }}">
                        @foreach ($packages as $package)
                            <option value="{{ $package->id }}" @selected((int) old('package_id') === $package->id)>
                                {{ $package->name }} — {{ number_format($package->price) }} تومان{{ $package->min_prepayment_percent ? ' (پیش‌پرداخت از '.$package->min_prepayment_percent.'٪)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1" for="customer_phone">موبایل مشتری</label>
                        <input id="customer_phone" name="customer_phone" dir="ltr" inputmode="tel" class="{{ $input }}" style="{{ $inputStyle }}" value="{{ old('customer_phone', request('phone')) }}" placeholder="09121234567">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1" for="customer_name">نام مشتری (فقط مشتری تازه)</label>
                        <input id="customer_name" name="customer_name" class="{{ $input }}" style="{{ $inputStyle }}" value="{{ old('customer_name') }}">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1" for="amount">مبلغ دریافتی حالا (تومان)</label>
                        <input id="amount" name="amount" dir="ltr" inputmode="numeric" class="{{ $input }}" style="{{ $inputStyle }}" value="{{ old('amount') }}">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1" for="discount_code">کد تخفیف (اختیاری)</label>
                        <input id="discount_code" name="discount_code" dir="ltr" class="{{ $input }}" style="{{ $inputStyle }}" value="{{ old('discount_code') }}">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1" for="note">یادداشت پرداخت (اختیاری)</label>
                    <input id="note" name="note" class="{{ $input }}" style="{{ $inputStyle }}" value="{{ old('note') }}" placeholder="مثلاً کارت‌خوان">
                </div>
                <button type="submit" class="px-5 py-2 rounded-lg text-sm font-bold" style="{{ $btn }}">ثبت فروش</button>
            </form>
        @endif
    </div>
@endsection
