@extends('layouts.app')
@section('title', 'قوانین به‌روز شد')

{{-- پذیرش دوباره‌ی قوانین مشتری (۲۰۲۶-۱۰-۲۸) — $pending از App\Services\Legal\LegalStatus::pending() --}}
@section('content')
    @php extract(\App\Support\Courses\CourseUi::customer()) @endphp
    @php($links = ['terms' => \App\Support\CentralUrl::route('legal.terms'), 'privacy' => \App\Support\CentralUrl::route('legal.privacy')])
    <div class="max-w-2xl mx-auto fade-in" data-terms-update>
        <div class="rounded-xl p-6" style="{{ $card }}">
            @include('courses._flash')
            <h1 class="text-2xl font-bold" style="{{ $gold }}">قوانین به‌روز شد</h1>
            <p class="text-sm mt-2 leading-7" style="{{ $dim }}">
                بخشی از قوانین ماهرو تغییر مهمی کرده است. خلاصه‌ی تغییرها را ببینید، متن کامل را بخوانید و اگر موافقید بپذیرید.
            </p>

            @foreach ($pending as $doc)
                <div class="rounded-lg p-4 mt-4" style="{{ $inner }}" data-pending-document="{{ $doc['document'] }}">
                    <div class="flex justify-between items-center gap-3">
                        <h2 class="font-bold" style="{{ $gold }}">{{ $doc['title'] }}</h2>
                        <span class="text-xs" style="{{ $dim }}">نسخه‌ی {{ to_persian_num(str_replace('-', '/', $doc['version'])) }}</span>
                    </div>
                    <ul class="mt-2 space-y-2 text-sm leading-7">
                        @foreach ($doc['changes'] as $change)
                            <li>
                                <span style="{{ $dim }}">{{ to_persian_num(str_replace('-', '/', $change['version'])) }}:</span>
                                @if ($change['major'])<strong class="text-xs px-2 rounded-full" style="background: rgb(var(--m-warn) / .15); color: rgb(var(--m-warn));">مهم</strong>@endif
                                {{ $change['summary'] }}
                            </li>
                        @endforeach
                    </ul>
                    @isset($links[$doc['document']])
                        <a href="{{ $links[$doc['document']] }}" target="_blank" rel="noopener" class="inline-block text-sm mt-2 underline" style="{{ $gold }}">متن کامل {{ $doc['title'] }}</a>
                    @endisset
                </div>
            @endforeach

            <form method="POST" action="{{ route('terms-update.accept') }}" class="mt-5">
                @csrf
                <label class="flex items-start gap-2 text-sm leading-7">
                    <input type="checkbox" name="accept_terms" value="1" required class="mt-1.5 rounded">
                    <span>متن تازه را خواندم و می‌پذیرم.</span>
                </label>
                <button type="submit" class="w-full mt-4 py-3 rounded-lg font-bold" style="{{ $btn }}">پذیرش و ادامه</button>
            </form>

            <div class="mt-6 pt-4 text-sm leading-7" style="border-top: 1px solid rgba(201,162,75,0.15);" data-terms-decline>
                <p style="{{ $dim }}">
                    اگر نمی‌پذیرید، نوبت یا دوره‌ی تازه نمی‌توانید بگیرید؛ نوبت‌ها و دوره‌های فعلی، لغو نوبت، شکایت و حذف حساب در دسترس است.
                </p>
                <div class="flex flex-wrap gap-2 mt-3">
                    <a href="{{ route('bookings.index') }}" class="px-4 py-2 rounded-lg" style="{{ $ghost }}">نوبت‌های من</a>
                    <a href="{{ route('profile.edit') }}#delete-account" class="px-4 py-2 rounded-lg" style="{{ $ghost }}">حذف حساب</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="px-4 py-2 rounded-lg" style="{{ $ghost }}">خروج</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
