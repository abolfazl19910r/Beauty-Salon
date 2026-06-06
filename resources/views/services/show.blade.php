@extends('layouts.app')

@section('title', $service->name)

@section('content')
    <div class="container mx-auto">
        <div class="max-w-4xl mx-auto fade-in">
            <div class="flex items-center text-sm text-gray-500 mb-6">
                <a href="{{ route('home') }}" class="hover:text-pink-500">خانه</a>
                <svg class="w-4 h-4 mx-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
                <a href="{{ route('services.index') }}" class="hover:text-pink-500">خدمات</a>
                <svg class="w-4 h-4 mx-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
                <span class="text-gray-900">{{ $service->name }}</span>
            </div>

            <div class="bg-white rounded-lg shadow-lg hover-shadow overflow-hidden">
                @if($service->image)
                    <img src="{{ $service->image_url }}"
                         alt="{{ $service->name }}"
                         class="w-full h-64 object-cover">
                @else
                    <div class="w-full h-48 bg-gradient-to-r from-pink-100 to-purple-100 flex items-center justify-center">
                        <svg class="w-20 h-20 text-pink-300" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                        </svg>
                    </div>
                @endif

                <div class="p-6">
                    <div class="flex justify-between items-start mb-6">
                        <div>
                            <h1 class="text-3xl font-bold mb-2">{{ $service->name }}</h1>
                            @if($service->category)
                                <div class="inline-flex items-center bg-pink-100 text-pink-700 rounded-full px-3 py-1">
                                    <svg class="w-4 h-4 ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                                    </svg>
                                    {{ $service->category->name }}
                                </div>
                            @endif
                        </div>
                        <div class="text-left">
                            <div class="text-2xl font-bold text-pink-600 persian-number">
                                {{ number_format($service->price) }} تومان
                            </div>
                            <div class="text-gray-500 flex items-center justify-end">
                                <svg class="w-4 h-4 ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                                مدت زمان: {{ $service->duration }} دقیقه
                            </div>
                        </div>
                    </div>

                    <div class="prose max-w-none mb-8 bg-gray-50 p-5 rounded-lg text-gray-700 leading-7">
                        {!! nl2br(e($service->description)) !!}
                    </div>

                    <div class="mb-8">
                        <h2 class="text-xl font-bold mb-4 flex items-center">
                            <svg class="w-6 h-6 ml-1 text-pink-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>
                            متخصصین این خدمت
                        </h2>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                            @foreach($specialists as $specialist)
                                <div class="bg-gray-50 rounded-lg p-4 hover:shadow-md transition-shadow">
                                    <div class="font-bold mb-1">{{ $specialist->name }}</div>
                                    @if($specialist->schedules->isNotEmpty())
                                        <div class="text-sm text-gray-500 flex items-center">
                                            <svg class="w-3 h-3 ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                                <line x1="8" y1="2" x2="8" y2="6"></line>
                                                <line x1="3" y1="10" x2="21" y2="10"></line>
                                            </svg>
                                            روزهای کاری: {{ $specialist->work_days }}
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="text-center">
                        <a href="{{ route('bookings.create', ['service' => $service->id]) }}"
                           class="inline-block bg-gradient-to-r from-pink-500 to-purple-600 text-white px-8 py-3 rounded-lg font-bold hover:opacity-90 transition-colors">
                            رزرو این خدمت
                        </a>
                    </div>
                </div>
            </div>

            @if($relatedServices->isNotEmpty())
                <div class="mt-12">
                    <h2 class="text-2xl font-bold mb-6 flex items-center">
                        <svg class="w-6 h-6 ml-1 text-pink-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                        </svg>
                        خدمات مرتبط
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        @foreach($relatedServices as $relatedService)
                            <div class="bg-white rounded-lg shadow-sm hover-shadow transition-all">
                                @if($relatedService->image)
                                    <img src="{{ $relatedService->image_url }}"
                                         alt="{{ $relatedService->name }}"
                                         class="w-full h-40 object-cover rounded-t-lg">
                                @else
                                    <div class="w-full h-40 bg-gradient-to-r from-pink-50 to-purple-50 rounded-t-lg flex items-center justify-center">
                                        <svg class="w-12 h-12 text-pink-200" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                                        </svg>
                                    </div>
                                @endif
                                <div class="p-4">
                                    <h3 class="font-bold mb-2">{{ $relatedService->name }}</h3>
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-500 persian-number">
                                            {{ number_format($relatedService->price) }} تومان
                                        </span>
                                        <a href="{{ route('services.show', $relatedService) }}"
                                           class="text-pink-600 hover:text-pink-700 flex items-center">
                                            <span>مشاهده</span>
                                            <svg class="w-4 h-4 mr-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M5 12h14"></path>
                                                <path d="M12 5l7 7-7 7"></path>
                                            </svg>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
