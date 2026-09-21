@extends('layouts.superadmin')

@section('title', $ticket->title)

@section('content')
    <div class="flex items-center justify-between mb-5">
        <h2 class="font-bold text-lg">{{ $ticket->title }}</h2>
        <a href="{{ route('superadmin.support-tickets.index') }}" style="color: var(--sa-accent);">→ بازگشت به صندوق</a>
    </div>

    @if (session('success'))
        <div class="sa-card mb-5" style="color: var(--sa-success);">{{ session('success') }}</div>
    @endif

    <div class="grid md:grid-cols-3 gap-5">
        <div class="md:col-span-2 space-y-5">
            <div class="sa-card">
                <div class="flex flex-wrap gap-4 mb-4" style="color: var(--sa-text-dim);">
                    <span>ثبت‌شده توسط: <span style="color: var(--sa-text);">{{ $ticket->user->name ?? '—' }}</span></span>
                    @if ($ticket->user?->salons?->isNotEmpty())
                        <span>سالن: <span style="color: var(--sa-text);">{{ $ticket->user->salons->first()->name }}</span></span>
                    @endif
                    <span>تاریخ ثبت: <span style="color: var(--sa-text);">{{ jalali_date($ticket->created_at) }}</span></span>
                </div>
                <p style="color: var(--sa-text); white-space: pre-line;">{{ $ticket->description }}</p>
            </div>

            <h3 class="font-bold" style="color: var(--sa-text-dim);">مکالمه</h3>

            <div class="space-y-3">
                @forelse ($ticket->messages as $message)
                    <div class="sa-card" style="{{ $message->is_staff_reply ? 'border-color: var(--sa-accent);' : '' }}">
                        <div class="flex items-center justify-between mb-2" style="color: var(--sa-text-dim);">
                            <span>{{ $message->is_staff_reply ? 'پشتیبانی پلتفرم' : ($message->user->name ?? '—') }}</span>
                            <span>{{ $message->formatted_created_at }}</span>
                        </div>
                        <p style="color: var(--sa-text); white-space: pre-line;">{{ $message->message }}</p>
                    </div>
                @empty
                    <p style="color: var(--sa-text-dim);">هنوز پیامی ثبت نشده است.</p>
                @endforelse
            </div>

            @unless ($ticket->isClosed())
                <form method="POST" action="{{ route('superadmin.support-tickets.reply', $ticket) }}" class="sa-card space-y-3">
                    @csrf
                    <label class="block" style="color: var(--sa-text);">پاسخ پشتیبانی</label>
                    <textarea name="message" rows="4" required maxlength="5000"
                              class="w-full rounded-lg px-3 py-2" style="border:1px solid var(--sa-border); background: var(--sa-bg); color: var(--sa-text);"></textarea>
                    <div class="flex justify-end">
                        <button type="submit" class="sa-btn">ارسال پاسخ</button>
                    </div>
                </form>
            @else
                <p class="text-center py-3" style="color: var(--sa-text-dim);">این تیکت بسته شده است.</p>
            @endunless
        </div>

        <div class="space-y-5">
            <div class="sa-card">
                <h3 class="font-bold mb-3" style="color: var(--sa-text-dim);">جزئیات</h3>
                <dl class="space-y-2">
                    <div class="flex justify-between">
                        <dt style="color: var(--sa-text-dim);">دسته‌بندی</dt>
                        <dd>{{ $ticket->category }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt style="color: var(--sa-text-dim);">وضعیت</dt>
                        <dd>{{ $ticket->status_text }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt style="color: var(--sa-text-dim);">واگذارشده به</dt>
                        <dd>{{ $ticket->assignedTo->name ?? '—' }}</dd>
                    </div>
                </dl>
            </div>

            <div class="sa-card">
                <h3 class="font-bold mb-3" style="color: var(--sa-text-dim);">تغییر اولویت / واگذاری</h3>
                <form method="POST" action="{{ route('superadmin.support-tickets.update', $ticket) }}" class="space-y-3">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="block mb-1" style="color: var(--sa-text-dim);">اولویت</label>
                        <select name="priority" class="w-full rounded-lg px-3 py-2" style="border:1px solid var(--sa-border); background: var(--sa-bg); color: var(--sa-text);">
                            <option value="low" @selected($ticket->priority === 'low')>کم</option>
                            <option value="medium" @selected($ticket->priority === 'medium')>متوسط</option>
                            <option value="high" @selected($ticket->priority === 'high')>زیاد</option>
                            <option value="urgent" @selected($ticket->priority === 'urgent')>فوری</option>
                        </select>
                    </div>
                    <div>
                        <label class="block mb-1" style="color: var(--sa-text-dim);">واگذاری به من</label>
                        <input type="hidden" name="assigned_to" value="{{ auth()->id() }}">
                    </div>
                    <button type="submit" class="sa-btn w-full">به‌روزرسانی</button>
                </form>
            </div>

            <div class="sa-card space-y-2">
                <h3 class="font-bold mb-1" style="color: var(--sa-text-dim);">تغییر وضعیت</h3>
                @unless ($ticket->isResolved())
                    <form method="POST" action="{{ route('superadmin.support-tickets.resolve', $ticket) }}">
                        @csrf
                        <button type="submit" class="sa-btn w-full" style="background: var(--sa-success);">حل‌شده</button>
                    </form>
                @endunless
                @unless ($ticket->isClosed())
                    <form method="POST" action="{{ route('superadmin.support-tickets.close', $ticket) }}">
                        @csrf
                        <button type="submit" class="sa-btn w-full" style="background: var(--sa-text-dim);">بستن تیکت</button>
                    </form>
                @endunless
                @if ($ticket->isResolved() || $ticket->isClosed())
                    <form method="POST" action="{{ route('superadmin.support-tickets.reopen', $ticket) }}">
                        @csrf
                        <button type="submit" class="sa-btn w-full" style="background: var(--sa-danger);">بازکردن دوباره</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
@endsection
