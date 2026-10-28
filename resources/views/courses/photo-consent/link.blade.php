@extends('layouts.app')
@section('title', 'رضایت عکس دوره')

{{-- لینک پیامکی رضایت عکس (۲۰۲۶-۱۰-۲۸) — PhotoConsentLinkController --}}
@section('content')
    @php extract(\App\Support\Courses\CourseUi::customer()) @endphp
    <div class="max-w-2xl mx-auto fade-in" data-photo-consent-link>
        <div class="rounded-xl p-6" style="{{ $card }}">
            @include('courses._flash')
            <h1 class="text-2xl font-bold" style="{{ $gold }}">رضایت عکس قبل و بعد</h1>
            <p class="text-sm mt-1" style="{{ $dim }}">دوره‌ی «{{ $course->name }}»</p>

            <div class="rounded-lg p-4 mt-4 text-sm leading-8" style="{{ $inner }}" data-photo-consent-text>
                {{ $text }}
                <p class="text-xs mt-2" style="{{ $dim }}">نسخه‌ی متن: {{ to_persian_num(str_replace('-', '/', $version)) }}</p>
            </div>

            @if ($row->isOpen())
                <form method="POST" action="{{ route('photo-consent.decide', ['token' => $token]) }}" class="mt-5" data-photo-consent-form>
                    @csrf
                    <label class="flex items-start gap-2 text-sm leading-7">
                        <input type="checkbox" name="read_text" value="1" class="mt-1.5 rounded">
                        <span>متن بالا را خواندم.</span>
                    </label>
                    <div class="grid grid-cols-2 gap-3 mt-4">
                        <button type="submit" name="decision" value="agree" class="py-3 rounded-lg font-bold" style="{{ $btn }}">موافقم</button>
                        <button type="submit" name="decision" value="decline" class="py-3 rounded-lg" style="{{ $ghost }}">موافق نیستم</button>
                    </div>
                </form>
            @else
                <p class="mt-5 text-sm" style="{{ $gold }}" data-photo-consent-result>
                    @if ($row->status === \App\Models\CoursePhotoConsent::PENDING)
                        مهلت این لینک گذشته است؛ اگر هنوز می‌خواهید پاسخ دهید از سالن بخواهید دوباره بفرستد یا از «دوره‌های من» اقدام کنید.
                    @else
                        پاسخ این درخواست ثبت شده است: {{ $row->label() }}.
                    @endif
                </p>
            @endif
        </div>
    </div>
@endsection
