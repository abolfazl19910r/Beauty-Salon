@extends('layouts.admin')
@section('title', $ticket->title)

@section('content')
    <div class="fade-in max-w-3xl">
        <div class="flex items-center justify-between mb-5">
            <h1 class="text-xl font-bold" style="color:var(--admin-text);">{{ $ticket->title }}</h1>
            <a href="{{ route('admin.support-tickets.index') }}" style="color: var(--admin-accent);">→ بازگشت به لیست</a>
        </div>

        @if(session('success'))
            <div class="mb-4 px-4 py-3 rounded-lg text-sm" style="background-color: rgba(34,197,94,0.1); color: #16A34A;">
                {{ session('success') }}
            </div>
        @endif

        <div class="rounded-xl p-5 mb-5" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
            <div class="flex flex-wrap gap-4 text-sm mb-4">
                <span style="color:var(--admin-text-dim);">دسته‌بندی: <span style="color:var(--admin-text);">{{ $ticket->category }}</span></span>
                <span style="color:var(--admin-text-dim);">اولویت: <span style="color:var(--admin-text);">{{ $ticket->priority_text }}</span></span>
                <span style="color:var(--admin-text-dim);">وضعیت: <span style="color:var(--admin-text);">{{ $ticket->status_text }}</span></span>
                <span class="persian-number" style="color:var(--admin-text-dim);">تاریخ ثبت: <span style="color:var(--admin-text);">{{ jalali_date($ticket->created_at) }}</span></span>
            </div>
            <p style="color:var(--admin-text); white-space: pre-line;">{{ $ticket->description }}</p>
        </div>

        <h2 class="text-sm font-bold mb-3" style="color:var(--admin-text-dim);">مکالمه</h2>

        <div class="space-y-3 mb-5">
            @forelse($ticket->messages as $message)
                <div class="rounded-xl p-4"
                     style="background: {{ $message->is_staff_reply ? 'var(--admin-accent-light)' : 'var(--admin-surface)' }}; border:1px solid var(--admin-border);">
                    <div class="flex items-center justify-between mb-2 text-xs" style="color:var(--admin-text-dim);">
                        <span>{{ $message->is_staff_reply ? 'پشتیبانی پلتفرم' : ($message->user->name ?? 'شما') }}</span>
                        <span class="persian-number">{{ $message->formatted_created_at }}</span>
                    </div>
                    <p style="color:var(--admin-text); white-space: pre-line;">{{ $message->message }}</p>
                </div>
            @empty
                <p class="text-sm" style="color:var(--admin-text-dim);">هنوز پیامی ثبت نشده است.</p>
            @endforelse
        </div>

        @unless($ticket->isClosed())
            <form action="{{ route('admin.support-tickets.reply', $ticket) }}" method="POST"
                  class="rounded-xl p-5 space-y-3" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                @csrf
                <label class="block text-sm mb-1" style="color:var(--admin-text);">پاسخ شما</label>
                <textarea name="message" rows="4" required maxlength="5000"
                          class="w-full px-3 py-2 rounded-lg" style="border:1px solid var(--admin-border); color:var(--admin-text);"></textarea>
                <div class="flex justify-end">
                    <button type="submit" class="px-4 py-2 rounded-lg text-sm text-white" style="background-color: var(--admin-accent);">
                        ارسال پیام
                    </button>
                </div>
            </form>
        @else
            <p class="text-sm text-center py-3" style="color:var(--admin-text-dim);">این تیکت بسته شده است.</p>
        @endunless
    </div>
@endsection
