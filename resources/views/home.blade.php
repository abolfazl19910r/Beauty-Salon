@extends('layouts.app')

@section('title', 'صفحه اصلی')

@section('content')
    <div class="relative bg-gradient-to-r from-blue-500 to-purple-600 text-white py-16 rounded-2xl mb-12">
        <div class="container mx-auto px-4">
            <div class="max-w-2xl">
                <h1 class="text-4xl md:text-5xl font-bold mb-4">به سالن زیبایی ما خوش آمدید</h1>
                <p class="text-xl mb-8">بهترین خدمات زیبایی با متخصصین مجرب</p>
                <a href="{{ route('bookings.create') }}"
                   class="bg-white text-blue-600 px-8 py-3 rounded-lg font-bold hover:bg-blue-50 transition-colors">
                    رزرو نوبت
                </a>
            </div>
        </div>
        <div class="absolute bottom-0 right-0 w-1/3 h-full opacity-10">
        </div>
    </div>

    <section class="mb-16">
        <div class="flex justify-between items-center mb-8">
            <h2 class="text-2xl font-bold">خدمات ما</h2>
            <a href="{{ route('services.index') }}" class="text-blue-500 hover:text-blue-600">
                مشاهده همه خدمات
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach($services as $service)
                <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-shadow p-6">
                    @if($service->image)
                        <img src="{{ $service->image_url }}"
                             alt="{{ $service->name }}"
                             class="w-full h-48 object-cover rounded-lg mb-4">
                    @endif
                    <h3 class="text-xl font-bold mb-2">{{ $service->name }}</h3>
                    <p class="text-gray-600 mb-4 line-clamp-2">{{ $service->description }}</p>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-500">
                            {{ number_format($service->price) }} تومان
                        </span>
                        <a href="{{ route('bookings.create', ['service' => $service->id]) }}"
                           class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition-colors">
                            رزرو
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <section class="mb-16">
        <h2 class="text-2xl font-bold mb-8">متخصصین ما</h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            @foreach($specialists as $specialist)
                <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-shadow p-6 text-center">
                    <div class="w-24 h-24 mx-auto mb-4 bg-gray-200 rounded-full flex items-center justify-center">
                        <span class="text-3xl text-gray-400">👤</span>
                    </div>
                    <h3 class="font-bold mb-2">{{ $specialist->name }}</h3>
                    <p class="text-gray-500 text-sm mb-4">متخصص زیبایی</p>
                </div>
            @endforeach
        </div>
    </section>

    <section class="mb-16">
        <h2 class="text-2xl font-bold mb-8">چرا ما را انتخاب کنید</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="text-center">
                <div class="w-16 h-16 mx-auto mb-4 bg-blue-100 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h3 class="font-bold mb-2">رزرو آنلاین</h3>
                <p class="text-gray-600">به راحتی و در هر ساعت از شبانه‌روز نوبت خود را رزرو کنید</p>
            </div>

            <div class="text-center">
                <div class="w-16 h-16 mx-auto mb-4 bg-blue-100 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h3 class="font-bold mb-2">متخصصین مجرب</h3>
                <p class="text-gray-600">بهره‌مندی از خدمات متخصصین با تجربه و حرفه‌ای</p>
            </div>

            <div class="text-center">
                <div class="w-16 h-16 mx-auto mb-4 bg-blue-100 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h3 class="font-bold mb-2">قیمت مناسب</h3>
                <p class="text-gray-600">ارائه خدمات با کیفیت در قیمت‌های رقابتی</p>
            </div>
        </div>
    </section>

    <section class="bg-white rounded-2xl shadow-sm p-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div>
                <h2 class="text-2xl font-bold mb-4">تماس با ما</h2>
                <p class="text-gray-600 mb-6">
                    برای کسب اطلاعات بیشتر و یا رزرو تلفنی با ما در تماس باشید
                </p>
                <div class="space-y-4">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-blue-500 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                        </svg>
                        <span>021-12345678</span>
                    </div>
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-blue-500 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        <span>info@beautysalon.com</span>
                    </div>
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-blue-500 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span>تهران، خیابان ولیعصر</span>
                    </div>
                </div>
            </div>

            <div>
                <iframe
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d6479.488840473815!2d51.41791915!3d35.7006776!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMzXCsDQyJzAyLjQiTiA1McKwMjUnMDQuNSJF!5e0!3m2!1sen!2s!4v1620120000000!5m2!1sen!2s"
                    class="w-full h-64 rounded-lg"
                    style="border:0;"
                    allowfullscreen=""
                    loading="lazy">
                </iframe>
            </div>
        </div>
    </section>
@endsection
