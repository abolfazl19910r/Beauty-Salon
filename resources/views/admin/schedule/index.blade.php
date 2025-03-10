@extends('layouts.admin')

@section('title', 'مدیریت زمانبندی')

@section('content')
    <div class="container px-6 mx-auto">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold flex items-center">
                <svg class="w-6 h-6 ml-2 text-blue-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
                مدیریت زمانبندی
            </h1>

            <div>
                <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center px-4 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                    <svg class="w-5 h-5 ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 12H5"></path>
                        <path d="M12 19l-7-7 7-7"></path>
                    </svg>
                    بازگشت به داشبورد
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition-all duration-300">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                        <svg class="w-8 h-8" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                    </div>
                    <div class="mr-4">
                        <p class="text-gray-500 text-sm">کل متخصصین</p>
                        <h2 class="text-2xl font-bold text-gray-700 persian-number">{{ $specialistsCount ?? 0 }}</h2>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition-all duration-300">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600">
                        <svg class="w-8 h-8" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="9 11 12 14 22 4"></polyline>
                            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                        </svg>
                    </div>
                    <div class="mr-4">
                        <p class="text-gray-500 text-sm">متخصصین فعال امروز</p>
                        <h2 class="text-2xl font-bold text-gray-700 persian-number">{{ $activeSpecialistsToday ?? 0 }}</h2>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition-all duration-300">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-red-100 text-red-600">
                        <svg class="w-8 h-8" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M8 19l4-4 4 4"></path>
                            <path d="M6 11l6-6 6 6"></path>
                            <path d="M12 19l0-12"></path>
                        </svg>
                    </div>
                    <div class="mr-4">
                        <p class="text-gray-500 text-sm">تعطیلات ماه جاری</p>
                        <h2 class="text-2xl font-bold text-gray-700 persian-number">{{ $currentMonthHolidays ?? 0 }}</h2>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition-all duration-300">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                        <svg class="w-8 h-8" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                        </svg>
                    </div>
                    <div class="mr-4">
                        <p class="text-gray-500 text-sm">درخواست‌های مرخصی</p>
                        <h2 class="text-2xl font-bold text-gray-700 persian-number">{{ $pendingLeaveRequests ?? 0 }}</h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-lg hover:shadow-xl transition-shadow duration-300 overflow-hidden">
            <div class="p-6">
                <div id="admin-schedule" class="fade-in">
                    <div class="flex justify-center items-center min-h-[400px]">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
                        <span class="mr-2 text-gray-500">در حال بارگذاری...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        window.initialData = {
            routes: {
                specialists: '{{ route('api.specialists.index') }}',
                schedules: '{{ route('api.schedules.index') }}',
                holidays: '{{ route('api.holidays.index') }}',
                leaves: '{{ route('api.leaves.index') }}'
            }
        };
    </script>
    @viteReactRefresh
    @vite(['resources/js/admin.jsx'])
@endpush
