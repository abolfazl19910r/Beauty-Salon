@extends('layouts.superadmin')

@section('title', 'اطلاعیه‌های سراسری')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <p class="text-sm" style="color: var(--sa-text-dim);">بنری که بالای پنل مدیر و/یا متخصص همه‌ی سالن‌ها نشان داده می‌شود؛ مثلاً خبر به‌روزرسانی یا قطعی برنامه‌ریزی‌شده.</p>
        <a href="{{ route('superadmin.announcements.create') }}" class="sa-btn">اطلاعیه‌ی تازه</a>
    </div>

    <div class="sa-card overflow-x-auto">
        <table class="w-full text-sm text-right">
            <thead>
            <tr style="color: var(--sa-text-dim);">
                <th class="py-3 px-4">عنوان</th>
                <th class="py-3 px-4">مخاطب</th>
                <th class="py-3 px-4">نوع</th>
                <th class="py-3 px-4">بازه‌ی نمایش</th>
                <th class="py-3 px-4">وضعیت</th>
                <th class="py-3 px-4"></th>
            </tr>
            </thead>
            <tbody>
            @forelse ($announcements as $a)
                <tr style="border-top: 1px solid var(--sa-border);">
                    <td class="py-3 px-4 font-bold">{{ $a->title }}</td>
                    <td class="py-3 px-4">{{ \App\Models\PlatformAnnouncement::AUDIENCES[$a->audience] ?? $a->audience }}</td>
                    <td class="py-3 px-4">{{ \App\Models\PlatformAnnouncement::LEVELS[$a->level] ?? $a->level }}</td>
                    <td class="py-3 px-4" style="color: var(--sa-text-dim);">{{ $a->starts_at ? jalali_date($a->starts_at) : 'از حالا' }} تا {{ $a->ends_at ? jalali_date($a->ends_at) : 'بی‌پایان' }}</td>
                    <td class="py-3 px-4">
                        @if ($a->isLive())<span style="color: var(--sa-success);">در حال نمایش</span>
                        @elseif (! $a->is_active)<span style="color: var(--sa-text-dim);">خاموش</span>
                        @else<span style="color: var(--sa-warn);">خارج از بازه</span>@endif
                    </td>
                    <td class="py-3 px-4 whitespace-nowrap">
                        <a href="{{ route('superadmin.announcements.edit', $a) }}" style="color: var(--sa-accent);">ویرایش</a>
                        <form method="POST" action="{{ route('superadmin.announcements.destroy', $a) }}" class="inline mr-3" onsubmit="return confirm('این اطلاعیه حذف شود؟')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" style="color: var(--sa-danger);">حذف</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="py-8 text-center" style="color: var(--sa-text-dim);">اطلاعیه‌ای نیست.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $announcements->links() }}</div>
    </div>
@endsection
