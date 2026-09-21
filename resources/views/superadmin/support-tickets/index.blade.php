@extends('layouts.superadmin')

@section('title', 'تیکت‌های پشتیبانی')

@section('content')
    <div class="flex items-center justify-between mb-5">
        <h2 class="font-bold text-lg">صندوق پشتیبانی</h2>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-5">
        <div class="sa-card">
            <p style="color: var(--sa-text-dim);">باز</p>
            <p class="text-xl font-bold mt-1" style="color: #D97706;">{{ $stats['open'] }}</p>
        </div>
        <div class="sa-card">
            <p style="color: var(--sa-text-dim);">در حال بررسی</p>
            <p class="text-xl font-bold mt-1" style="color: var(--sa-accent);">{{ $stats['in_progress'] }}</p>
        </div>
        <div class="sa-card">
            <p style="color: var(--sa-text-dim);">حل‌شده</p>
            <p class="text-xl font-bold mt-1" style="color: var(--sa-success);">{{ $stats['resolved'] }}</p>
        </div>
        <div class="sa-card">
            <p style="color: var(--sa-text-dim);">بسته‌شده</p>
            <p class="text-xl font-bold mt-1" style="color: var(--sa-text-dim);">{{ $stats['closed'] }}</p>
        </div>
    </div>

    <form method="GET" class="sa-card mb-5 flex flex-wrap items-end gap-3">
        <div>
            <label class="block mb-1" style="color: var(--sa-text-dim);">وضعیت</label>
            <select name="status" class="rounded-lg px-3 py-2" style="border:1px solid var(--sa-border); background: var(--sa-bg); color: var(--sa-text);">
                <option value="">همه</option>
                <option value="open" @selected(request('status') === 'open')>باز</option>
                <option value="in_progress" @selected(request('status') === 'in_progress')>در حال بررسی</option>
                <option value="resolved" @selected(request('status') === 'resolved')>حل‌شده</option>
                <option value="closed" @selected(request('status') === 'closed')>بسته‌شده</option>
            </select>
        </div>
        <div>
            <label class="block mb-1" style="color: var(--sa-text-dim);">اولویت</label>
            <select name="priority" class="rounded-lg px-3 py-2" style="border:1px solid var(--sa-border); background: var(--sa-bg); color: var(--sa-text);">
                <option value="">همه</option>
                <option value="low" @selected(request('priority') === 'low')>کم</option>
                <option value="medium" @selected(request('priority') === 'medium')>متوسط</option>
                <option value="high" @selected(request('priority') === 'high')>زیاد</option>
                <option value="urgent" @selected(request('priority') === 'urgent')>فوری</option>
            </select>
        </div>
        <div>
            <label class="block mb-1" style="color: var(--sa-text-dim);">واگذاری</label>
            <select name="assigned" class="rounded-lg px-3 py-2" style="border:1px solid var(--sa-border); background: var(--sa-bg); color: var(--sa-text);">
                <option value="">همه</option>
                <option value="unassigned" @selected(request('assigned') === 'unassigned')>واگذارنشده</option>
                <option value="me" @selected(request('assigned') === 'me')>واگذارشده به من</option>
            </select>
        </div>
        <button type="submit" class="sa-btn">فیلتر</button>
        <a href="{{ route('superadmin.support-tickets.index') }}" style="color: var(--sa-text-dim);">پاک‌کردن فیلتر</a>
    </form>

    <div class="sa-card overflow-x-auto">
        <table class="w-full text-sm text-right">
            <thead>
                <tr style="color: var(--sa-text-dim); border-bottom: 1px solid var(--sa-border);">
                    <th class="py-3 px-4">عنوان</th>
                    <th class="py-3 px-4">ثبت‌شده توسط</th>
                    <th class="py-3 px-4">دسته‌بندی</th>
                    <th class="py-3 px-4">اولویت</th>
                    <th class="py-3 px-4">وضعیت</th>
                    <th class="py-3 px-4">واگذارشده به</th>
                    <th class="py-3 px-4">تاریخ ثبت</th>
                    <th class="py-3 px-4">عملیات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($tickets as $ticket)
                    <tr style="border-bottom: 1px solid var(--sa-border);">
                        <td class="py-3 px-4">{{ $ticket->title }}</td>
                        <td class="py-3 px-4">{{ $ticket->user->name ?? '—' }}</td>
                        <td class="py-3 px-4">{{ $ticket->category }}</td>
                        <td class="py-3 px-4">{{ $ticket->priority_text }}</td>
                        <td class="py-3 px-4">
                            @if ($ticket->isOpen())
                                <span style="color: #D97706;">{{ $ticket->status_text }}</span>
                            @elseif ($ticket->isInProgress())
                                <span style="color: var(--sa-accent);">{{ $ticket->status_text }}</span>
                            @elseif ($ticket->isResolved())
                                <span style="color: var(--sa-success);">{{ $ticket->status_text }}</span>
                            @else
                                <span style="color: var(--sa-text-dim);">{{ $ticket->status_text }}</span>
                            @endif
                        </td>
                        <td class="py-3 px-4">{{ $ticket->assignedTo->name ?? '—' }}</td>
                        <td class="py-3 px-4">{{ jalali_date($ticket->created_at) }}</td>
                        <td class="py-3 px-4">
                            <a href="{{ route('superadmin.support-tickets.show', $ticket) }}" style="color: var(--sa-accent);">مشاهده</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="py-6 px-4 text-center" style="color: var(--sa-text-dim);">تیکتی یافت نشد.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $tickets->links() }}</div>
@endsection
