@extends('layouts.admin')

@section('title', 'ویرایش متخصص')

@section('content')
    <div class="max-w-4xl mx-auto">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 mb-2">ویرایش متخصص</h1>
                <p class="text-sm text-gray-500">ویرایش اطلاعات {{ $specialist->name }}</p>
            </div>
            <a href="{{ route('admin.specialists.show', ['specialist' => $specialist->id]) }}"
               class="mt-3 md:mt-0 flex items-center px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition-colors shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
                بازگشت
            </a>
        </div>

        <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200 overflow-hidden">
            <div class="p-5 border-b border-gray-100">
                <h2 class="text-lg font-semibold text-gray-800">اطلاعات متخصص</h2>
                <p class="text-sm text-gray-500 mt-1">مشخصات و خدمات قابل ارائه توسط متخصص</p>
            </div>

            <form action="{{ route('admin.specialists.update', ['specialist' => $specialist->id]) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="p-5 space-y-6">
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h3 class="text-md font-medium text-gray-800 mb-3">اطلاعات شخصی</h3>

                        <div class="space-y-4">
                            <div>
                                <label for="name" class="block mb-2 text-sm font-medium text-gray-700">نام متخصص</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                    </div>
                                    <input type="text" id="name" name="name" value="{{ old('name', $specialist->name) }}"
                                           class="w-full pr-10 border-gray-300 focus:ring-blue-500 focus:border-blue-500 rounded-lg transition-colors"
                                           required>
                                </div>
                                @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="phone" class="block mb-2 text-sm font-medium text-gray-700">شماره تماس</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                        </svg>
                                    </div>
                                    <input type="text" id="phone" name="phone" value="{{ old('phone', $specialist->phone) }}"
                                           class="w-full pr-10 border-gray-300 focus:ring-blue-500 focus:border-blue-500 rounded-lg transition-colors"
                                           required dir="ltr">
                                </div>
                                @error('phone')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="email" class="block mb-2 text-sm font-medium text-gray-700">ایمیل</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                    <input type="email" id="email" name="email" value="{{ old('email', $specialist->email) }}"
                                           class="w-full pr-10 border-gray-300 focus:ring-blue-500 focus:border-blue-500 rounded-lg transition-colors"
                                           required dir="ltr">
                                </div>
                                @error('email')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-md font-medium text-gray-800 mb-3">انتخاب خدمات قابل ارائه</h3>
                        <div class="space-y-4">
                            @foreach($services as $category)
                                <div class="border border-gray-200 rounded-lg p-4 hover:border-blue-300 transition-colors">
                                    <h4 class="font-medium text-gray-800 mb-3 flex items-center">
                                        <span class="flex items-center justify-center w-6 h-6 rounded-full bg-blue-100 text-blue-600 ml-2 text-xs">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </span>
                                        {{ $category->name }}
                                    </h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        @foreach($category->services as $service)
                                            <label class="flex items-center p-2 rounded-lg hover:bg-gray-50 transition-colors">
                                                <input type="checkbox"
                                                       name="services[]"
                                                       value="{{ $service->id }}"
                                                       class="h-5 w-5 text-blue-600 focus:ring-blue-500 border-gray-300 rounded transition-colors"
                                                    {{ in_array($service->id, old('services', $specialist->services->pluck('id')->toArray())) ? 'checked' : '' }}>
                                                <span class="mr-2">{{ $service->name }}</span>
                                                <span class="text-sm text-gray-500 mr-auto">{{ number_format($service->price) }} تومان</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @error('services')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="p-5 bg-gray-50 border-t border-gray-100 flex flex-col-reverse sm:flex-row justify-between gap-3">
                    <a href="{{ route('admin.specialists.show', ['specialist' => $specialist->id]) }}"
                       class="w-full sm:w-auto inline-flex justify-center items-center px-6 py-2.5 border border-gray-300 shadow-sm text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        انصراف
                    </a>
                    <button type="submit" class="w-full sm:w-auto inline-flex justify-center items-center px-6 py-2.5 border border-transparent shadow-sm text-sm font-medium rounded-lg text-white bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                        ذخیره تغییرات
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mt-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="mr-3">
                    <p class="text-sm text-blue-700">
                        پس از ذخیره اطلاعات متخصص، می‌توانید برنامه کاری و مرخصی‌های او را نیز تنظیم نمایید.
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const categoryHeaders = document.querySelectorAll('h4');
            categoryHeaders.forEach(header => {
                header.addEventListener('click', function() {
                    const serviceList = this.nextElementSibling;
                    serviceList.classList.toggle('hidden');
                    const icon = this.querySelector('svg');
                    icon.classList.toggle('rotate-180');
                });
            });
        });
    </script>
@endpush
