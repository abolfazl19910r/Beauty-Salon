@extends('layouts.admin')
@section('title', 'ارسال دستی به مشتری‌های قدیمی')

@section('content')
    <div class="fade-in" style="max-width: 64rem;">
        @php extract(\App\Support\Courses\CourseUi::admin()) @endphp
        @include('admin.courses._ui')
        <div class="flex justify-between items-center mb-3 flex-wrap gap-3">
            <h1 class="text-xl font-bold" style="color:var(--admin-text);">ارسال دستی به مشتری‌های قدیمی</h1>
            <div class="flex gap-2">
                <a href="{{ route('admin.winback.index') }}" class="px-4 py-2 rounded-lg text-sm" style="{{ $ghost }}">پیگیری خودکار</a>
                @unless ($limitsError)
                    <a href="{{ route('admin.winback.campaigns.create') }}" class="px-4 py-2 rounded-lg text-sm font-bold" style="{{ $btn }}">کمپین تازه</a>
                @endunless
            </div>
        </div>
        <p class="text-sm mb-5" style="{{ $dim }} line-height:1.9;">
            پیامک دعوت (و اگر بخواهید کد تخفیف شخصی) برای مشتری‌هایی که خیلی وقت است نیامده‌اند — تا ۲ سال پیش. حداکثر ۵۰۰ نفر در هر کمپین و ۲ کمپین
            در هر ۳۰ روز، فقط ساعت ۹ تا ۲۰ و آهسته (۲۰ پیامک در دقیقه)، و همیشه ۲۰٪ سهمیه‌ی ماه برای پیامک‌های نوبت می‌ماند. کسی که در ۹۰ روز اخیر پیامک
            پیگیری گرفته یا انصراف داده، پیامک نمی‌گیرد.
        </p>
        @if ($limitsError)
            <div class="rounded-lg p-3 text-sm mb-5" style="background:rgb(var(--m-warn) / .14); color:rgb(var(--m-warn));">{{ $limitsError }}</div>
        @endif

        <div class="rounded-xl overflow-hidden" style="{{ $box }}">
            @forelse ($campaigns as $campaign)
                <a href="{{ route('admin.winback.campaigns.show', $campaign) }}" class="block px-4 py-3" style="border-top:1px solid var(--admin-border); color:var(--admin-text);">
                    <div class="flex justify-between items-center gap-3">
                        <span class="font-medium text-sm">{{ $campaign->name }}</span>
                        <span class="text-xs px-2 py-0.5 rounded-full" style="background:var(--admin-accent-light); color:var(--admin-accent);">{{ $campaign->statusLabel() }}</span>
                    </div>
                    <div class="text-xs mt-1 persian-number" style="{{ $dim }}">
                        {{ verta($campaign->created_at)->format('Y/m/d') }} · {{ to_persian_num((string) $campaign->sent) }} از {{ to_persian_num((string) $campaign->targeted) }} پیامک
                        @if ($campaign->pause_reason) · {{ $campaign->pauseReasonLabel() }}@endif
                    </div>
                </a>
            @empty
                <p class="px-4 py-8 text-center text-sm" style="{{ $dim }}">هنوز کمپینی نساخته‌اید.</p>
            @endforelse
        </div>
    </div>
@endsection
