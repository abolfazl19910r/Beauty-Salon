@extends('layouts.superadmin')

@section('title', 'کارهای ناموفق صف')

@section('content')
    {{-- ⭐ ۲۰۲۶-۱۰-۱۵: جدول failed_jobs (پیامک، گزارش، اعلان و … که پس از همه‌ی تلاش‌ها شکست خوردند) — قبلاً هیچ دیدی نبود. --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <p class="text-sm" style="color: var(--sa-text-dim);">کارهای پس‌زمینه‌ای که بعد از همه‌ی تلاش‌ها شکست خوردند. «دوباره» آن را به صف برمی‌گرداند.</p>
        @if ($jobs->total() > 0)
            <form method="POST" action="{{ route('superadmin.failed-jobs.retry-all') }}">
                @csrf
                <button type="submit" class="sa-btn">همه را دوباره امتحان کن</button>
            </form>
        @endif
    </div>

    <div class="sa-card overflow-x-auto">
        <table class="w-full text-sm text-right">
            <thead>
            <tr style="color: var(--sa-text-dim);">
                <th class="py-3 px-4">زمان</th>
                <th class="py-3 px-4">کار</th>
                <th class="py-3 px-4">صف</th>
                <th class="py-3 px-4">خطا</th>
                <th class="py-3 px-4"></th>
            </tr>
            </thead>
            <tbody>
            @forelse ($jobs as $job)
                <tr style="border-top: 1px solid var(--sa-border);">
                    <td class="py-3 px-4 whitespace-nowrap">{{ \Morilog\Jalali\Jalalian::fromCarbon(\Illuminate\Support\Carbon::parse($job->failed_at))->format('Y/m/d H:i') }}</td>
                    <td class="py-3 px-4 font-bold" dir="ltr" style="text-align: right;">{{ $job->display_name }}</td>
                    <td class="py-3 px-4" style="color: var(--sa-text-dim);">{{ $job->queue }}</td>
                    <td class="py-3 px-4" dir="ltr" style="text-align: left; color: var(--sa-danger); max-width: 28rem; overflow-wrap: anywhere;">{{ \Illuminate\Support\Str::limit($job->error, 220) }}</td>
                    <td class="py-3 px-4 whitespace-nowrap">
                        <form method="POST" action="{{ route('superadmin.failed-jobs.retry', $job->uuid) }}" class="inline">
                            @csrf
                            <button type="submit" class="text-sm font-bold" style="color: var(--sa-accent);">دوباره</button>
                        </form>
                        <form method="POST" action="{{ route('superadmin.failed-jobs.forget', $job->uuid) }}" class="inline mr-3">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-sm" style="color: var(--sa-text-dim);">حذف</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="py-8 text-center" style="color: var(--sa-text-dim);">کار ناموفقی نیست.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $jobs->links() }}</div>
    </div>
@endsection
