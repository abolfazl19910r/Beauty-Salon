@extends('layouts.admin')
@section('title', 'بسته‌های دوره‌ی درمانی')

@section('content')
    <div class="fade-in" style="max-width: 64rem;">
        @php extract(\App\Support\Courses\CourseUi::admin()) @endphp
        @include('admin.courses._ui')
        <div class="flex justify-between items-center mb-5 flex-wrap gap-3">
            <div>
                <h1 class="text-xl font-bold" style="color:var(--admin-text);">بسته‌های دوره‌ی درمانی</h1>
                <p class="text-sm mt-1" style="{{ $dim }}">هر بسته یک خدمت با چند جلسه است؛ مشتری یک‌جا (یا با پیش‌پرداخت) می‌خرد و موعد هر جلسه خودکار یادآوری می‌شود.</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.courses.index') }}" class="px-4 py-2 rounded-lg text-sm" style="{{ $ghost }}">دوره‌های فروخته‌شده</a>
                <a href="{{ route('admin.courses.packages.create') }}" class="px-4 py-2 rounded-lg text-sm font-bold" style="{{ $btn }}">+ بسته‌ی تازه</a>
            </div>
        </div>

        <div class="rounded-xl overflow-x-auto mb-5" style="{{ $box }}">
            <table class="w-full text-sm text-right">
                <thead><tr style="{{ $dim }} border-bottom:1px solid var(--admin-border);">
                    <th class="p-3">نام</th><th class="p-3">خدمت</th><th class="p-3">جلسه / فاصله</th><th class="p-3">قیمت</th><th class="p-3">پیش‌پرداخت</th><th class="p-3">فروش</th><th class="p-3">وضعیت</th><th class="p-3"></th>
                </tr></thead>
                <tbody>
                    @forelse ($packages as $package)
                        <tr style="border-bottom:1px solid var(--admin-border);" data-package="{{ $package->id }}">
                            <td class="p-3 font-medium" style="color:var(--admin-text);">{{ $package->name }}</td>
                            <td class="p-3">{{ $package->service?->name }}@if ($package->specialist)<div class="text-xs" style="{{ $dim }}">{{ $package->specialist->name }}</div>@endif</td>
                            <td class="p-3 persian-number">{{ to_persian_num((string) $package->sessions_count) }} جلسه، هر {{ to_persian_num((string) $package->interval_days) }} روز</td>
                            <td class="p-3 persian-number">{{ number_format($package->price) }}</td>
                            <td class="p-3 persian-number">{{ $package->min_prepayment_percent ? to_persian_num((string) $package->min_prepayment_percent).'٪' : 'فقط کل' }}</td>
                            <td class="p-3 persian-number">{{ to_persian_num((string) $package->customer_courses_count) }}</td>
                            <td class="p-3 text-xs">{{ $package->is_active ? 'فعال' : 'غیرفعال' }} · {{ $package->is_public ? 'فروش آنلاین' : 'فقط حضوری' }}</td>
                            <td class="p-3"><a href="{{ route('admin.courses.packages.edit', $package) }}" style="color:var(--admin-accent);">ویرایش</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="p-6 text-center" style="{{ $dim }}">هنوز بسته‌ای تعریف نشده است.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <form method="POST" action="{{ route('admin.courses.settings.update') }}" class="rounded-xl p-5" style="{{ $box }}">
            @csrf @method('PUT')
            <h2 class="font-bold mb-3" style="color:var(--admin-text);">تنظیمات</h2>
            <label class="flex items-start gap-3 cursor-pointer">
                <input type="checkbox" name="burn_late_cancellation" value="1" class="w-5 h-5 mt-0.5" @checked($setting->burn_late_cancellation)>
                <span>
                    <span class="font-medium" style="color:var(--admin-text);">جلسه‌ای که مشتری دیرهنگام لغو کند، سوخت شود</span>
                    <span class="block text-xs mt-1" style="{{ $dim }}">«دیرهنگام» همان مهلت لغو تنظیمات مالی سالن است. خاموش (پیش‌فرض): هر لغوی جلسه را برمی‌گرداند. لغو توسط سالن هرگز جلسه را نمی‌سوزاند.</span>
                </span>
            </label>
            <button type="submit" class="mt-4 px-5 py-2 rounded-lg text-sm font-bold" style="{{ $btn }}">ذخیره</button>
        </form>
    </div>
@endsection
