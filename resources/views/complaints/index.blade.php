@extends('layouts.app')

@section('title', 'شکایت و پیگیری')

@section('content')
    <div class="max-w-3xl mx-auto fade-in" data-complaints>
        <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-m-gold2" style="font-family:'Mahru','Vazirmatn',sans-serif;font-weight:800;">شکایت و پیگیری</h1>
                <p class="text-sm text-m-tx/60 mt-1 leading-7">شکایت از سالن یا از پلتفرم {{ config('brand.name') }}. مدیر پلتفرم حداکثر ظرف {{ to_persian_num((string) $hours) }} ساعت پاسخ می‌دهد.</p>
            </div>
            <a href="{{ route('complaints.create') }}" class="px-4 py-2 rounded-lg text-sm font-bold bg-gradient-to-l from-[#C9A24B] to-[#E6CD8A] text-m-ongold">ثبت شکایت</a>
        </div>

        <div class="bg-m-card rounded-2xl border border-m-gold/10 divide-y divide-m-gold/10">
            @forelse ($complaints as $complaint)
                <a href="{{ route('complaints.show', $complaint) }}" class="flex flex-wrap items-center justify-between gap-2 px-5 py-4 hover:bg-m-gold/5" data-complaint-row>
                    <span>
                        <span class="font-bold">{{ $complaint->title }}</span>
                        <span class="block text-xs text-m-tx/50">{{ $complaint->categoryLabel() }} · {{ verta($complaint->created_at)->format('Y/m/d H:i') }}</span>
                    </span>
                    <span class="text-xs">
                        @if ($complaint->first_responded_at)
                            <span class="text-m-ok">پاسخ داده شد</span>
                        @elseif (in_array($complaint->status, ['resolved', 'closed'], true))
                            <span class="text-m-tx/60">بسته شد</span>
                        @else
                            <span class="text-m-warn">در انتظار پاسخ</span>
                        @endif
                    </span>
                </a>
            @empty
                <p class="px-5 py-8 text-center text-sm text-m-tx/60">هنوز شکایتی ثبت نکرده‌اید.</p>
            @endforelse
        </div>
        <div class="mt-4">{{ $complaints->links() }}</div>
    </div>
@endsection
