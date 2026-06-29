@extends('layouts.admin')
@section('title', 'نتایج جستجو')

@section('content')
    <div class="container px-6 mx-auto">

        <div class="flex items-center justify-between mb-6">
            <h1 class="text-xl font-bold flex items-center gap-2" style="color:var(--admin-text)">
                <svg class="w-5 h-5" style="color:var(--admin-accent)" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                نتایج جستجو
                @if(!empty($query))
                    <span class="text-base font-normal" style="color:var(--admin-text-dim)">برای «{{ $query }}»</span>
                @endif
            </h1>
        </div>

        <div class="rounded-xl overflow-hidden" style="background:var(--admin-surface);border:1px solid var(--admin-border)">

            @if(empty($query))
                <div class="py-16 text-center" style="color:var(--admin-text-dim)">
                    <svg class="w-12 h-12 mx-auto mb-4" style="color:var(--admin-border)" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                    <p>لطفاً عبارت جستجوی خود را وارد کنید</p>
                </div>

            @elseif(count($results) > 0)
                <div class="divide-y" style="border-color:var(--admin-border)">
                    @foreach($results as $key => $items)
                        @if(count($items) > 0)
                            <div class="p-6">
                                <h3 class="text-sm font-semibold mb-3 flex items-center gap-2" style="color:var(--admin-text-dim)">
                                    <span class="px-2 py-0.5 rounded-full text-xs" style="background:var(--admin-accent-light);color:var(--admin-accent)">{{ count($items) }}</span>
                                    {{ $key }}
                                </h3>
                                <div class="space-y-2">
                                    @foreach($items as $item)
                                        @php
                                            $link = '#';
                                            if ($key === 'کاربران')     $link = route('admin.users.show',      $item->id);
                                            if ($key === 'متخصصین')    $link = route('admin.specialists.show', $item->id);
                                            if ($key === 'خدمات')      $link = route('admin.services.edit',    $item->id);
                                        @endphp
                                        <a href="{{ $link }}"
                                           class="flex items-center gap-3 p-3 rounded-lg transition-colors"
                                           style="border:1px solid var(--admin-border)"
                                           onmouseover="this.style.background='var(--admin-accent-light)'"
                                           onmouseout="this.style.background='transparent'">
                                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0"
                                                 style="background:var(--admin-accent-light);color:var(--admin-accent)">
                                                {{ mb_substr($item->name ?? '؟', 0, 1) }}
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium" style="color:var(--admin-text)">{{ $item->name ?? 'بدون نام' }}</p>
                                                <p class="text-xs" style="color:var(--admin-text-dim)">
                                                    @if($key === 'کاربران')    {{ $item->phone }}
                                                    @elseif($key === 'متخصصین') {{ $item->phone }}
                                                    @elseif($key === 'خدمات')   {{ $item->duration ?? '' }} دقیقه
                                                    @endif
                                                </p>
                                            </div>
                                            <svg class="w-4 h-4 mr-auto" style="color:var(--admin-text-light)" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>

            @else
                <div class="py-16 text-center" style="color:var(--admin-text-dim)">
                    <svg class="w-12 h-12 mx-auto mb-4" style="color:var(--admin-border)" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                    <p class="font-medium mb-1" style="color:var(--admin-text)">نتیجه‌ای یافت نشد</p>
                    <p class="text-sm">عبارت «{{ $query }}» در هیچ بخشی پیدا نشد</p>
                </div>
            @endif

        </div>

    </div>
@endsection
