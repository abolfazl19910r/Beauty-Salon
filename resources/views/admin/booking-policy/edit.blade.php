@extends('layouts.admin')
@section('title', 'نحوه‌ی رزرو')

{{--
    بسته‌ی 5ه (۲۰۲۶-۱۰-۲۸): «نحوه‌ی رزرو» سالن — AdminBookingPolicyController. هر بخش فرم جدای خودش را دارد (partials).
--}}
@php
    $input = 'w-full rounded-lg px-3 py-2 text-sm';
    $inputStyle = 'border:1px solid var(--admin-border); background:var(--admin-bg); color:var(--admin-text);';
    $card = 'rounded-xl p-5 space-y-4';
    $cardStyle = 'background:var(--admin-surface); border:1px solid var(--admin-border);';
@endphp

@section('content')
    <div class="max-w-3xl mx-auto space-y-5">
        <div>
            <h1 class="text-xl font-bold" style="color: var(--admin-text);">نحوه‌ی رزرو</h1>
            <p class="text-sm mt-1" style="color: var(--admin-text-dim);">
                این تنظیمات برای رزرو آنلاین مشتری‌ها (سایت و اپ) است. نوبتی که خود شما یا منشی در پنل ثبت می‌کنید از این محدودیت‌ها معاف است.
            </p>
        </div>

        @if ($errors->any())
            <div class="rounded-lg p-3 text-sm" style="background:rgb(var(--m-bad) / .14); color:rgb(var(--m-bad));">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        @include('admin.booking-policy.partials.mode')
        @includeIf('admin.booking-policy.partials.loyalty')
        @includeIf('admin.booking-policy.partials.limits')
        @includeIf('admin.booking-policy.partials.no-show')
        @includeIf('admin.booking-policy.partials.attendance')
    </div>
@endsection
