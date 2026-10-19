@extends('layouts.app')

@section('title', 'رزرو نوبت')

@section('content')
    {{-- بسته‌ی «ح»، ۸: رزرو سالن موقتاً متوقف است (BookingReservationController::create) --}}
    <div class="max-w-lg mx-auto text-center py-12 space-y-4" data-booking-paused-page>
        <h1 class="text-xl font-bold" style="color: rgb(var(--m-warn));">رزرو موقتاً متوقف است</h1>
        <p class="text-m-tx/80 leading-8">{{ $salon->bookingPauseMessage() }}</p>
        <p class="text-sm text-m-tx/60">نوبت‌های ثبت‌شده‌ی شما سر جایشان هستند و یادآوری‌شان مثل همیشه می‌آید.</p>
        <div class="flex flex-wrap justify-center gap-3 pt-2">
            <a href="{{ route('bookings.index') }}" class="px-5 py-2 rounded-lg font-bold bg-m-gold text-m-onsolid">نوبت‌های من</a>
            <a href="{{ route('home') }}" class="px-5 py-2 rounded-lg border border-m-gold/40 text-m-gold">صفحه‌ی اصلی</a>
        </div>
    </div>
@endsection
