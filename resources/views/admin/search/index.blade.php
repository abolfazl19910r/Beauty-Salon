@extends('layouts.admin')

@section('title', 'نتایج جستجو')

@section('content')

    <div class="p-6 bg-white rounded-xl shadow-lg hover-shadow-lg transition">
        <h2 class="text-2xl font-bold text-gray-800 mb-4">نتایج جستجو برای: <span class="text-blue-600">"{{ $query ?? '' }}"</span></h2>

        @if(empty($query))
            <div class="text-center py-10 text-gray-500">
                <p>لطفاً عبارت جستجوی خود را وارد کنید.</p>
            </div>
        @elseif (count($results) > 0)
            <div class="space-y-6">
                @foreach($results as $key => $items)
                    <div class="border-b pb-4">
                        <h3 class="text-xl font-semibold text-gray-700 mb-3">{{ $key }} ({{ count($items) }})</h3>
                        <div class="space-y-2">
                            @forelse($items as $item)
                                @php
                                    $link = '#';

                                    if ($key === 'کاربران') {
                                        $link = route('admin.users.show', $item->id);
                                    } elseif ($key === 'متخصصین') {
                                        $link = route('admin.specialists.show', $item->id);
                                    } elseif ($key === 'خدمات') {
                                        $link = route('admin.services.edit', $item->id);
                                    }
                                @endphp

                                <a href="{{ $link }}" class="block p-3 bg-gray-50 hover:bg-gray-100 rounded-lg transition">
                                    <p class="font-medium text-gray-800">{{ $item->name ?? 'بدون نام' }}</p>
                                    <p class="text-sm text-gray-500">
                                        @if($key === 'کاربران')
                                            {{ $item->phone }}
                                        @elseif($key === 'خدمات')
                                            {{ $item->duration ?? '' }} دقیقه
                                        @elseif($key === 'متخصصین')
                                            {{ $item->phone }}
                                        @endif
                                    </p>
                                </a>
                            @empty
                                <p class="text-gray-500 text-sm">نتیجه‌ای در این بخش یافت نشد.</p>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-10 text-gray-500">
                <p>نتیجه‌ای برای عبارت "{{ $query }}" یافت نشد.</p>
            </div>
        @endif
    </div>

@endsection
