@extends('layouts.admin')
@section('title', 'قوانین به‌روز شد')

{{-- پذیرش دوباره‌ی مالک سالن (۲۰۲۶-۱۰-۲۸) — $pending از App\Services\Legal\LegalStatus::pending() --}}
@section('content')
    @php extract(\App\Support\Courses\CourseUi::admin()) @endphp
    @php($links = ['salon_terms' => \App\Support\CentralUrl::route('legal.terms').'#salons', 'privacy' => \App\Support\CentralUrl::route('legal.privacy')])
    <div class="max-w-2xl mx-auto" data-terms-update>
        <h1 class="text-xl font-bold" style="color: var(--admin-text);">قوانین به‌روز شد</h1>
        <p class="text-sm mt-1 mb-5 leading-7" style="{{ $dim }}">
            بخشی از قوانین {{ config('brand.name') }} تغییر مهمی کرده است. تا مالک «{{ $salon->name }}» نسخه‌ی تازه را نپذیرد، پنل مدیریت
            برای او بسته است؛ رزرو مشتری‌ها و پنل منشی و متخصص‌ها مثل همیشه کار می‌کند.
        </p>

        @if ($errors->any())
            <div class="mb-4 p-3 rounded-lg text-sm" style="background: rgb(var(--m-bad) / .1); color: rgb(var(--m-bad));">{{ $errors->first() }}</div>
        @endif

        @foreach ($pending as $doc)
            <div class="rounded-xl p-5 mb-4" style="{{ $box }}" data-pending-document="{{ $doc['document'] }}">
                <div class="flex justify-between items-center gap-3">
                    <h2 class="font-bold" style="color: var(--admin-text);">{{ $doc['title'] }}</h2>
                    <span class="text-xs" style="{{ $dim }}">نسخه‌ی {{ to_persian_num(str_replace('-', '/', $doc['version'])) }}</span>
                </div>
                <ul class="mt-2 space-y-2 text-sm leading-7" style="color: var(--admin-text);">
                    @foreach ($doc['changes'] as $change)
                        <li>
                            <span style="{{ $dim }}">{{ to_persian_num(str_replace('-', '/', $change['version'])) }}:</span>
                            @if ($change['major'])<strong class="text-xs px-2 rounded-full" style="background: rgb(var(--m-warn) / .15); color: rgb(var(--m-warn));">مهم</strong>@endif
                            {{ $change['summary'] }}
                        </li>
                    @endforeach
                </ul>
                @isset($links[$doc['document']])
                    <a href="{{ $links[$doc['document']] }}" target="_blank" rel="noopener" class="inline-block text-sm mt-2 underline" style="color: var(--admin-accent);">متن کامل {{ $doc['title'] }}</a>
                @endisset
            </div>
        @endforeach

        <form method="POST" action="{{ route('admin.terms-update.accept') }}" class="rounded-xl p-5" style="{{ $box }}">
            @csrf
            <label class="flex items-start gap-2 text-sm leading-7" style="color: var(--admin-text);">
                <input type="checkbox" name="accept_terms" value="1" required class="mt-1.5 rounded">
                <span>متن تازه را از طرف «{{ $salon->name }}» خواندم و می‌پذیرم.</span>
            </label>
            <button type="submit" class="w-full mt-4 py-2.5 rounded-lg font-bold" style="{{ $btn }}">پذیرش و ادامه</button>
        </form>

        <div class="mt-5 text-sm leading-7" style="{{ $dim }}" data-terms-decline>
            اگر نمی‌پذیرید، می‌توانید از پنل خارج شوید یا از
            <a href="{{ route('admin.support-tickets.index') }}" class="underline" style="color: var(--admin-accent);">پشتیبانی</a>
            برای پرسش یا بستن حساب سالن پیام بدهید.
            <form method="POST" action="{{ route('logout') }}" class="inline">
                @csrf
                <button type="submit" class="underline mr-1" style="color: var(--admin-accent);">خروج</button>
            </form>
        </div>
    </div>
@endsection
