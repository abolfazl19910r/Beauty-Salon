@extends('layouts.app')
@section('title', 'متخصص‌های سالن')

{{-- ⭐ صفحه‌ی عمومی جستجوی متخصص‌ها (۲۰۲۶-۰۹-۲۴) — قبلاً view نداشت (۵۰۰). --}}
@section('content')
    <div class="max-w-6xl mx-auto px-4 py-10" style="color: var(--rasta-cream);">
        <h1 class="text-2xl md:text-3xl font-bold mb-2" style="color: var(--rasta-gold-light);">متخصص‌های {{ $currentSalonName }}</h1>
        <p class="text-sm opacity-75 mb-6">متخصص مورد نظرتان را پیدا کنید، پروفایلش را ببینید و آنلاین نوبت بگیرید.</p>

        @include('specialists.partials.nav')

        <form method="GET" action="{{ route('specialists.search') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-8">
            <input type="text" name="name" value="{{ $search }}" placeholder="نام متخصص" maxlength="100"
                   class="md:col-span-2 rounded-lg px-4 py-2" style="background: var(--rasta-brown); border: 1px solid rgba(201,162,75,.25); color: var(--rasta-cream);">
            <select name="service_id" class="rounded-lg px-4 py-2" style="background: var(--rasta-brown); border: 1px solid rgba(201,162,75,.25); color: var(--rasta-cream);">
                <option value="">همه‌ی خدمات</option>
                @foreach ($services as $service)
                    <option value="{{ $service->id }}" @selected($serviceId === $service->id)>{{ $service->name }}</option>
                @endforeach
            </select>
            <div class="flex gap-2">
                <select name="sort" class="flex-1 rounded-lg px-3 py-2" style="background: var(--rasta-brown); border: 1px solid rgba(201,162,75,.25); color: var(--rasta-cream);">
                    <option value="">پیش‌فرض</option>
                    <option value="name" @selected($sort === 'name')>نام</option>
                    <option value="rating" @selected($sort === 'rating')>بیشترین امتیاز</option>
                </select>
                <button type="submit" class="px-5 py-2 rounded-lg font-bold" style="background: var(--rasta-gold); color: #1A1410;">جستجو</button>
            </div>
        </form>

        @if ($specialists->isEmpty())
            <p class="text-center opacity-70 py-10">متخصصی با این مشخصات پیدا نشد.</p>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                @foreach ($specialists as $specialist)
                    @include('specialists.partials.card', ['specialist' => $specialist])
                @endforeach
            </div>
            <div class="mt-8">{{ $specialists->links() }}</div>
        @endif
    </div>
@endsection
