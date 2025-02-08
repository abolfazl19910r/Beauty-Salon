@extends('layouts.app')

@section('title', 'خدمات')

@section('content')
    <div class="container mx-auto max-w-7xl">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold mb-2">خدمات ما</h1>
            <p class="text-gray-600">لیست کامل خدمات قابل ارائه در سالن زیبایی</p>
        </div>

        <!-- Categories Filter -->
        <div class="bg-white rounded-lg shadow-sm p-4 mb-8">
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('services.index') }}"
                   class="px-4 py-2 rounded-full {{ !request('category') ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    همه خدمات
                </a>
                @foreach($categories as $category)
                    <a href="{{ route('services.index', ['category' => $category->id]) }}"
                       class="px-4 py-2 rounded-full {{ request('category') == $category->id ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                        {{ $category->name }}
                    </a>
                @endforeach
            </div>
        </div>

        <!-- Services Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($services as $service)
                <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow">
                    @if($service->image)
                        <img src="{{ $service->image_url }}"
                             alt="{{ $service->name }}"
                             class="w-full h-48 object-cover rounded-t-lg">
                    @endif
                    <div class="p-6">
                        <h3 class="text-xl font-bold mb-2">{{ $service->name }}</h3>
                        <p class="text-gray-600 mb-4 line-clamp-2">{{ $service->description }}</p>
                        <div class="text-gray-500 text-sm mb-4">
                            <div>مدت زمان: {{ $service->duration }} دقیقه</div>
                            <div>قیمت: {{ number_format($service->price) }} تومان</div>
                        </div>
                        <div class="flex flex-col gap-2">
                            <a href="{{ route('services.show', $service) }}"
                               class="text-center text-blue-500 hover:text-blue-600 py-2 border rounded-lg">
                                مشاهده جزئیات
                            </a>
                            <a href="{{ route('bookings.create', ['service' => $service->id]) }}"
                               class="bg-blue-500 text-white text-center py-2 rounded-lg hover:bg-blue-600 transition-colors">
                                رزرو نوبت
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full text-center py-12 bg-gray-50 rounded-lg">
                    <p class="text-gray-500">هیچ خدمتی یافت نشد!</p>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        <div class="mt-8">
            {{ $services->links() }}
        </div>
    </div>
@endsection
