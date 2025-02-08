@extends('layouts.app')

@section('title', $service->name)

@section('content')
    <div class="container mx-auto">
        <div class="max-w-4xl mx-auto">
            <!-- Breadcrumb -->
            <div class="flex items-center text-sm text-gray-500 mb-6">
                <a href="{{ route('home') }}" class="hover:text-gray-700">خانه</a>
                <span class="mx-2">/</span>
                <a href="{{ route('services.index') }}" class="hover:text-gray-700">خدمات</a>
                <span class="mx-2">/</span>
                <span class="text-gray-900">{{ $service->name }}</span>
            </div>

            <!-- Service Details -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                @if($service->image)
                    <img src="{{ $service->image_url }}"
                         alt="{{ $service->name }}"
                         class="w-full h-64 object-cover">
                @endif

                <div class="p-6">
                    <div class="flex justify-between items-start mb-6">
                        <div>
                            <h1 class="text-3xl font-bold mb-2">{{ $service->name }}</h1>
                            @if($service->category)
                                <div class="text-blue-500">
                                    {{ $service->category->name }}
                                </div>
                            @endif
                        </div>
                        <div class="text-left">
                            <div class="text-2xl font-bold text-blue-500">
                                {{ number_format($service->price) }} تومان
                            </div>
                            <div class="text-gray-500">
                                مدت زمان: {{ $service->duration }} دقیقه
                            </div>
                        </div>
                    </div>

                    <div class="prose max-w-none mb-8">
                        {!! nl2br(e($service->description)) !!}
                    </div>

                    <!-- Available Specialists -->
                    <div class="mb-8">
                        <h2 class="text-xl font-bold mb-4">متخصصین این خدمت</h2>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                            @foreach($specialists as $specialist)
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <div class="font-bold mb-1">{{ $specialist->name }}</div>
                                    @if($specialist->schedules->isNotEmpty())
                                        <div class="text-sm text-gray-500">
                                            روزهای کاری: {{ $specialist->workDays }}
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Booking Button -->
                    <div class="text-center">
                        <a href="{{ route('bookings.create', ['service' => $service->id]) }}"
                           class="inline-block bg-blue-500 text-white px-8 py-3 rounded-lg font-bold hover:bg-blue-600">
                            رزرو این خدمت
                        </a>
                    </div>
                </div>
            </div>

            <!-- Related Services -->
            @if($relatedServices->isNotEmpty())
                <div class="mt-12">
                    <h2 class="text-2xl font-bold mb-6">خدمات مرتبط</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        @foreach($relatedServices as $relatedService)
                            <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow">
                                @if($relatedService->image)
                                    <img src="{{ $relatedService->image_url }}"
                                         alt="{{ $relatedService->name }}"
                                         class="w-full h-40 object-cover rounded-t-lg">
                                @endif
                                <div class="p-4">
                                    <h3 class="font-bold mb-2">{{ $relatedService->name }}</h3>
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-500">
                                            {{ number_format($relatedService->price) }} تومان
                                        </span>
                                        <a href="{{ route('services.show', $relatedService) }}"
                                           class="text-blue-500 hover:text-blue-600">
                                            مشاهده
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
