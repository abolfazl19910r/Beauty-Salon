@extends('layouts.app')

@section('title', 'شکایت: '.$complaint->title)

@section('content')
    <div class="max-w-3xl mx-auto fade-in" data-complaint-show>
        <a href="{{ route('complaints.index') }}" class="text-sm text-m-tx/60 hover:text-m-gold2">← شکایت‌های من</a>
        <h1 class="text-xl font-bold text-m-gold2 mt-3">{{ $complaint->title }}</h1>
        <p class="text-xs text-m-tx/50 mt-1 persian-number">
            {{ $complaint->categoryLabel() }} · ثبت {{ verta($complaint->created_at)->format('Y/m/d H:i') }}
            @if ($complaint->first_responded_at)
                · پاسخ {{ verta($complaint->first_responded_at)->format('Y/m/d H:i') }}
            @elseif ($complaint->response_due_at)
                · مهلت پاسخ تا {{ verta($complaint->response_due_at)->format('Y/m/d H:i') }}
            @endif
        </p>

        <div class="mt-5 space-y-3">
            <div class="bg-m-card rounded-2xl border border-m-gold/10 p-4 text-sm leading-7 whitespace-pre-line">{{ $complaint->description }}</div>
            @foreach ($complaint->messages as $message)
                <div class="rounded-2xl border p-4 text-sm leading-7 whitespace-pre-line {{ $message->is_staff_reply ? 'border-m-gold/30 bg-m-gold/5' : 'border-m-gold/10 bg-m-card' }}">
                    <div class="text-xs text-m-tx/50 mb-1">{{ $message->is_staff_reply ? 'پاسخ '.config('brand.name') : 'شما' }} · {{ verta($message->created_at)->format('Y/m/d H:i') }}</div>
                    {{ $message->message }}
                </div>
            @endforeach
        </div>

        @unless ($complaint->status === 'closed')
            <form method="POST" action="{{ route('complaints.reply', $complaint) }}" class="mt-5 space-y-3">
                @csrf
                <textarea name="message" rows="3" maxlength="3000" required placeholder="پیام تازه…"
                          class="w-full rounded-lg bg-m-bg border border-m-gold/20 p-3 text-sm">{{ old('message') }}</textarea>
                <x-input-error :messages="$errors->get('message')" />
                <button type="submit" class="px-5 py-2 rounded-lg text-sm font-bold bg-gradient-to-l from-[#C9A24B] to-[#E6CD8A] text-m-ongold">ارسال</button>
            </form>
        @endunless
    </div>
@endsection
