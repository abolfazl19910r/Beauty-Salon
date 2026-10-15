@extends('layouts.admin')
@section('title', 'دوره‌های درمانی')

@section('content')
    <div class="fade-in" style="max-width: 72rem;">
        @php extract(\App\Support\Courses\CourseUi::admin()) @endphp
        @include('admin.courses._ui')
        <div class="flex justify-between items-center mb-5 flex-wrap gap-3">
            <h1 class="text-xl font-bold" style="color:var(--admin-text);">دوره‌های درمانی فروخته‌شده</h1>
            <div class="flex gap-2">
                @if ($canSell)
                    <a href="{{ route('admin.courses.packages.index') }}" class="px-4 py-2 rounded-lg text-sm" style="{{ $ghost }}">بسته‌ها و تنظیمات</a>
                    <a href="{{ route('admin.courses.sell') }}" class="px-4 py-2 rounded-lg text-sm font-bold" style="{{ $btn }}">فروش حضوری دوره</a>
                @else
                    @include('admin.partials.feature-locked', ['feature' => \App\Support\Features\Feature::TreatmentCourses])
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-5">
            <div class="rounded-xl p-4" style="{{ $box }}"><div class="text-xs" style="{{ $dim }}">دوره‌های فعال</div><div class="text-2xl font-bold mt-1 persian-number">{{ to_persian_num(number_format($stats['active'])) }}</div></div>
            <div class="rounded-xl p-4" style="{{ $box }}"><div class="text-xs" style="{{ $dim }}">موعد گذشته، بدون نوبت</div><div class="text-2xl font-bold mt-1 persian-number" style="color:rgb(var(--m-warn));">{{ to_persian_num(number_format($stats['overdue'])) }}</div></div>
            <div class="rounded-xl p-4" style="{{ $box }}"><div class="text-xs" style="{{ $dim }}">ارزش جلسه‌های پیش‌فروش‌شده‌ی مانده</div><div class="text-2xl font-bold mt-1 persian-number">{{ to_persian_num(number_format($stats['liability'])) }} <span class="text-xs">تومان</span></div></div>
        </div>

        <form method="GET" class="rounded-xl p-4 mb-5 grid grid-cols-1 md:grid-cols-5 gap-3" style="{{ $box }}">
            <input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="نام یا موبایل مشتری" class="{{ $input }}" style="{{ $inputStyle }}">
            <select name="status" class="{{ $input }}" style="{{ $inputStyle }}">
                <option value="">همه‌ی وضعیت‌ها</option>
                @foreach (\App\Models\CustomerCourse::STATUSES as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['status'] ?? null) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="service_id" class="{{ $input }}" style="{{ $inputStyle }}">
                <option value="">همه‌ی خدمات</option>
                @foreach ($services as $service)
                    <option value="{{ $service->id }}" @selected((int) ($filters['service_id'] ?? 0) === $service->id)>{{ $service->name }}</option>
                @endforeach
            </select>
            <select name="filter" class="{{ $input }}" style="{{ $inputStyle }}">
                <option value="">بدون فیلتر ویژه</option>
                @foreach (\App\Http\Controllers\Admin\Courses\AdminCustomerCourseController::FILTERS as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['filter'] ?? null) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit" class="px-4 py-2 rounded-lg text-sm font-bold" style="{{ $btn }}">فیلتر</button>
        </form>

        <div class="rounded-xl overflow-x-auto" style="{{ $box }}">
            <table class="w-full text-sm text-right">
                <thead><tr style="{{ $dim }} border-bottom:1px solid var(--admin-border);">
                    <th class="p-3">مشتری</th><th class="p-3">دوره</th><th class="p-3">پیشرفت</th><th class="p-3">پرداخت</th><th class="p-3">موعد بعد</th><th class="p-3">مهلت</th><th class="p-3">وضعیت</th>
                </tr></thead>
                <tbody>
                    @forelse ($courses as $course)
                        <tr style="border-bottom:1px solid var(--admin-border);" data-course="{{ $course->id }}">
                            <td class="p-3"><a href="{{ route('admin.courses.show', $course) }}" style="color:var(--admin-accent);">{{ $course->user?->name ?? '—' }}</a><div class="text-xs" dir="ltr" style="{{ $dim }}">{{ $course->user?->phone }}</div></td>
                            <td class="p-3">{{ $course->name }}</td>
                            <td class="p-3 persian-number">{{ to_persian_num((string) $course->sessions_used) }} از {{ to_persian_num((string) $course->sessions_total) }}</td>
                            <td class="p-3 persian-number">{{ number_format($course->amount_paid) }} / {{ number_format($course->price_total) }}</td>
                            <td class="p-3 persian-number">{{ $course->status === 'active' && $course->next_due_at ? verta($course->next_due_at)->format('Y/m/d') : '—' }}</td>
                            <td class="p-3 persian-number">{{ $course->expires_at ? verta($course->expires_at)->format('Y/m/d') : '—' }}</td>
                            <td class="p-3">{{ $course->statusLabel() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="p-6 text-center" style="{{ $dim }}">دوره‌ای پیدا نشد.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="p-4">{{ $courses->links() }}</div>
        </div>
    </div>
@endsection
