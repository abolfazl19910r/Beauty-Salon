@extends('layouts.superadmin')

@section('title', 'احراز سالن‌ها')

@section('content')
    <div class="flex flex-wrap gap-2 mb-5">
        @foreach (['pending' => 'در انتظار', 'approved' => 'تأییدشده', 'rejected' => 'ردشده'] as $key => $label)
            <a href="{{ route('superadmin.verifications.index', ['status' => $key]) }}" class="sa-btn {{ $status === $key ? '' : 'sa-btn-ghost' }}" @if ($status === $key) aria-current="page" @endif>
                {{ $label }} ({{ to_persian_num((string) ($counts[$key] ?? 0)) }})
            </a>
        @endforeach
    </div>

    <div class="sa-card overflow-x-auto">
        <table class="w-full text-sm text-right">
            <thead><tr style="color: var(--sa-text-dim);">
                <th class="py-3 px-4">سالن</th><th class="py-3 px-4">مالک</th><th class="py-3 px-4">پروانه</th><th class="py-3 px-4">ارسال</th><th class="py-3 px-4"></th>
            </tr></thead>
            <tbody>
            @forelse ($verifications as $v)
                <tr style="border-top: 1px solid var(--sa-border);" data-verification-row>
                    <td class="py-3 px-4">{{ $v->salon?->name }} <span class="text-xs" dir="ltr" style="color: var(--sa-text-dim);">{{ $v->salon?->slug }}</span></td>
                    <td class="py-3 px-4">{{ $v->owner_name }} <span class="text-xs" dir="ltr">{{ $v->maskedNationalCode() }}</span></td>
                    <td class="py-3 px-4">{{ $v->licenseLabel() }}</td>
                    <td class="py-3 px-4 whitespace-nowrap">{{ \Morilog\Jalali\Jalalian::fromCarbon($v->created_at)->format('Y/m/d H:i') }}</td>
                    <td class="py-3 px-4"><a href="{{ route('superadmin.verifications.show', $v) }}" style="color: var(--sa-accent);">بررسی</a></td>
                </tr>
            @empty
                <tr><td colspan="5" class="py-6 px-4 text-center" style="color: var(--sa-text-dim);">موردی نیست.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $verifications->links() }}</div>
@endsection
