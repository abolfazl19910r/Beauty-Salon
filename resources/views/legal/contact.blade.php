@extends('layouts.legal')

@section('title', 'تماس با ما')

@section('content')
    @php
        $missing = ! $platform['landline_phone'] || ! $platform['contact_email'] || ! $platform['postal_address'];
    @endphp

    <p>
        {{ $platform['brand'] }} یک سکوی (پلتفرم) نوبت‌دهی آنلاین است: سالن‌های زیبایی مستقل روی آن خدمات خود را عرضه می‌کنند و نوبت می‌گیرند.
        برای پرسش درباره‌ی یک نوبت یا خدمت، اول با همان سالن تماس بگیرید (اطلاعات تماس هر سالن پایین صفحه‌ی خود سالن است).
        برای پشتیبانی پلتفرم، شکایت از یک سالن یا از خود پلتفرم، از راه‌های زیر استفاده کنید.
    </p>

    <dl class="mt-6 grid gap-4" data-legal-contact>
        @if ($platform['legal_name'])
            <div>
                <dt class="text-sm text-m-tx/60">صاحب امتیاز</dt>
                <dd class="font-bold">{{ $platform['legal_name'] }}@if ($platform['national_id']) <span class="text-sm font-normal persian-number">(شناسه‌ی ملی {{ $platform['national_id'] }})</span>@endif</dd>
            </div>
        @endif
        @if ($platform['landline_phone'])
            <div>
                <dt class="text-sm text-m-tx/60">تلفن ثابت</dt>
                <dd><a href="tel:{{ preg_replace('/[^0-9+]/', '', $platform['landline_phone']) }}" dir="ltr" class="persian-number" data-legal-landline>{{ $platform['landline_phone'] }}</a></dd>
            </div>
        @endif
        @if ($platform['support_phone'])
            <div>
                <dt class="text-sm text-m-tx/60">تلفن پشتیبانی</dt>
                <dd><a href="tel:{{ preg_replace('/[^0-9+]/', '', $platform['support_phone']) }}" dir="ltr" class="persian-number">{{ $platform['support_phone'] }}</a></dd>
            </div>
        @endif
        @if ($platform['contact_email'])
            <div>
                <dt class="text-sm text-m-tx/60">ایمیل</dt>
                <dd><a href="mailto:{{ $platform['contact_email'] }}" dir="ltr" data-legal-email>{{ $platform['contact_email'] }}</a></dd>
            </div>
        @endif
        @if ($platform['postal_address'])
            <div>
                <dt class="text-sm text-m-tx/60">نشانی پستی</dt>
                <dd data-legal-address>{{ $platform['postal_address'] }}@if ($platform['postal_code']) <span class="persian-number">— کد پستی {{ $platform['postal_code'] }}</span>@endif</dd>
            </div>
        @endif
    </dl>

    @if ($missing)
        <p class="mt-6 text-sm text-m-tx/60" data-legal-contact-incomplete>اطلاعات تماس به‌زودی کامل می‌شود.</p>
    @endif

    <h2>شکایت</h2>
    <p>
        شکایت از یک سالن یا از پلتفرم را از پنل کاربری خود در همان سالن («پروفایل ← شکایت و پیگیری») یا با ایمیل و تلفن بالا ثبت کنید.
        مدیر سالن‌ها هم از پنل خود («تیکت‌ها ← شکایت از پلتفرم») ثبت می‌کنند.
        حداکثر ظرف {{ to_persian_num((string) $complaintHours) }} ساعت پاسخ می‌دهیم. اگر پاسخ ما قانع‌کننده نبود، می‌توانید از سامانه‌ی رسیدگی به
        شکایات نماد اعتماد الکترونیکی (اینماد) پیگیری کنید.
    </p>
@endsection
