@extends('layouts.admin')
@section('title', 'تیکت‌های پشتیبانی')

@section('content')
    <div class="fade-in">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-5">
            <div>
                <h1 class="text-xl font-bold" style="color:var(--admin-text);">تیکت‌های پشتیبانی</h1>
                <p class="text-sm mt-0.5" style="color:var(--admin-text-dim);">پیام‌های شما به تیم پشتیبانی پلتفرم</p>
            </div>
            <a href="{{ route('admin.support-tickets.create') }}"
               class="px-4 py-2 rounded-lg text-sm font-medium text-white transition hover:opacity-90"
               style="background-color: var(--admin-accent);">
                + تیکت جدید
            </a>
        </div>

        @if(session('success'))
            <div class="mb-4 px-4 py-3 rounded-lg text-sm" style="background-color: rgba(34,197,94,0.1); color: #16A34A;">
                {{ session('success') }}
            </div>
        @endif

        <div class="rounded-xl overflow-hidden" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                    <tr style="background-color: var(--admin-accent-light);">
                        <th class="px-4 py-3 text-right" style="color:var(--admin-text-dim);">عنوان</th>
                        <th class="px-4 py-3 text-right" style="color:var(--admin-text-dim);">دسته‌بندی</th>
                        <th class="px-4 py-3 text-right" style="color:var(--admin-text-dim);">اولویت</th>
                        <th class="px-4 py-3 text-right" style="color:var(--admin-text-dim);">وضعیت</th>
                        <th class="px-4 py-3 text-right" style="color:var(--admin-text-dim);">تاریخ ثبت</th>
                        <th class="px-4 py-3 text-right" style="color:var(--admin-text-dim);">عملیات</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($tickets as $ticket)
                        <tr style="border-top: 1px solid var(--admin-border);">
                            <td class="px-4 py-3" style="color:var(--admin-text);">{{ $ticket->title }}</td>
                            <td class="px-4 py-3" style="color:var(--admin-text-dim);">{{ $ticket->category }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 rounded-full text-xs" style="background-color: var(--admin-accent-light); color: var(--admin-accent);">
                                    {{ $ticket->priority_text }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                @if($ticket->isOpen())
                                    <span style="color:#D97706;">{{ $ticket->status_text }}</span>
                                @elseif($ticket->isInProgress())
                                    <span style="color:#2563EB;">{{ $ticket->status_text }}</span>
                                @elseif($ticket->isResolved())
                                    <span style="color:#16A34A;">{{ $ticket->status_text }}</span>
                                @else
                                    <span style="color:var(--admin-text-dim);">{{ $ticket->status_text }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 persian-number" style="color:var(--admin-text-dim);">{{ jalali_date($ticket->created_at) }}</td>
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.support-tickets.show', $ticket) }}" style="color: var(--admin-accent);">مشاهده</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center" style="color:var(--admin-text-dim);">
                                هنوز تیکتی ثبت نکرده‌اید.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4">
                {{ $tickets->links() }}
            </div>
        </div>
    </div>
@endsection
