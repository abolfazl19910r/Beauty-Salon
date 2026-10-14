@extends('layouts.admin')
@section('title', 'مشتری‌ها')

@section('content')
    <div class="fade-in" style="max-width: 72rem;">
        @php extract(\App\Support\Courses\CourseUi::admin()) @endphp
        @include('admin.courses._ui')
        <div class="flex justify-between items-center mb-5 flex-wrap gap-3">
            <h1 class="text-xl font-bold" style="color:var(--admin-text);">مشتری‌ها</h1>
        </div>

        <form method="GET" class="rounded-xl p-4 mb-5 flex gap-3" style="{{ $box }}">
            <input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="نام یا موبایل" class="{{ $input }}" style="{{ $inputStyle }}">
            <button class="px-4 py-2 rounded-lg text-sm font-bold" style="{{ $btn }}">جست‌وجو</button>
        </form>

        <div class="rounded-xl overflow-hidden" style="{{ $box }}">
            @if ($customers->isEmpty())
                <p class="px-4 py-8 text-center text-sm" style="{{ $dim }}">مشتری‌ای پیدا نشد.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                        <tr style="background:var(--admin-accent-light); color:var(--admin-text-dim);">
                            <th class="px-4 py-2.5 text-right font-medium">نام</th>
                            <th class="px-4 py-2.5 text-right font-medium">موبایل</th>
                            <th class="px-4 py-2.5 text-right font-medium">نوبت انجام‌شده</th>
                            <th class="px-4 py-2.5 text-right font-medium">آخرین مراجعه</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($customers as $customer)
                            <tr style="border-top:1px solid var(--admin-border);">
                                <td class="px-4 py-2.5"><a href="{{ route('admin.customers.show', $customer->id) }}" style="color:var(--admin-accent);">{{ $customer->name }}</a></td>
                                <td class="px-4 py-2.5" dir="ltr" style="color:var(--admin-text);">{{ $customer->phone }}</td>
                                <td class="px-4 py-2.5 persian-number" style="color:var(--admin-text);">{{ to_persian_num((string) $customer->completed_count) }}</td>
                                <td class="px-4 py-2.5 persian-number" style="{{ $dim }}">{{ $customer->last_visit_at ? verta($customer->last_visit_at)->format('Y/m/d') : '—' }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
        <div class="mt-4">{{ $customers->links() }}</div>
    </div>
@endsection
