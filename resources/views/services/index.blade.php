@extends('layouts.app')

@section('title', 'خدمات')

@section('content')
    <div class="container mx-auto max-w-7xl">
        <div class="mb-8 fade-in">
            <h1 class="text-3xl font-bold mb-2 flex items-center">
                <svg class="w-8 h-8 ml-2 text-pink-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                </svg>
                خدمات ما
            </h1>
            <p class="text-gray-600">لیست کامل خدمات قابل ارائه در سالن زیبایی</p>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-4 mb-8 hover-shadow fade-in">
            <h2 class="text-lg font-bold mb-3 flex items-center">
                <svg class="w-5 h-5 ml-1 text-pink-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                </svg>
                دسته‌بندی خدمات
            </h2>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('services.index') }}"
                   class="px-4 py-2 rounded-full transition-colors {{ !request('category') ? 'bg-gradient-to-r from-pink-500 to-purple-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    همه خدمات
                </a>
                @foreach($categories as $category)
                    <a href="{{ route('services.index', ['category' => $category->id]) }}"
                       class="px-4 py-2 rounded-full transition-colors {{ request('category') == $category->id ? 'bg-gradient-to-r from-pink-500 to-purple-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                        {{ $category->name }}
                    </a>
                @endforeach
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 fade-in">
            @forelse($services as $service)
                <div class="bg-white rounded-lg shadow-sm hover-shadow transition-all">
                    @if($service->image)
                        <img src="{{ $service->image_url }}"
                             alt="{{ $service->name }}"
                             class="w-full h-48 object-cover rounded-t-lg">
                    @else
                        <div class="w-full h-48 bg-gradient-to-r from-pink-100 to-purple-100 rounded-t-lg flex items-center justify-center">
                            <svg class="w-16 h-16 text-pink-300" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                            </svg>
                        </div>
                    @endif
                    <div class="p-6">
                        <h3 class="text-xl font-bold mb-2">{{ $service->name }}</h3>
                        <p class="text-gray-600 mb-4 line-clamp-2">{{ $service->description }}</p>
                        <div class="text-gray-500 text-sm mb-4 space-y-1">
                            <div class="flex items-center">
                                <svg class="w-4 h-4 ml-1 text-pink-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                                <span>مدت زمان: {{ $service->duration }} دقیقه</span>
                            </div>
                            <div class="flex items-center">
                                <svg class="w-4 h-4 ml-1 text-pink-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="12" y1="1" x2="12" y2="23"></line>
                                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                                </svg>
                                <span class="persian-number">قیمت: {{ number_format($service->price) }} تومان</span>
                            </div>
                        </div>
                        <div class="flex flex-col gap-2">
                            <a href="{{ route('services.show', $service) }}"
                               class="text-center text-pink-600 hover:text-pink-700 py-2 border border-pink-200 rounded-lg transition-colors hover:bg-pink-50">
                                مشاهده جزئیات
                            </a>
                            <a href="{{ route('bookings.create', ['service' => $service->id]) }}"
                               class="bg-gradient-to-r from-pink-500 to-purple-600 text-white text-center py-2 rounded-lg hover:opacity-90 transition-colors">
                                رزرو نوبت
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full text-center py-12 bg-gray-50 rounded-lg">
                    <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="8" y1="12" x2="16" y2="12"></line>
                    </svg>
                    <p class="text-gray-500">هیچ خدمتی یافت نشد!</p>
                </div>
            @endforelse
        </div>

        <div class="mt-8">
            {{ $services->links() }}
        </div>
    </div>
@endsection
