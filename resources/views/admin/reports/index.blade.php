@extends('layouts.admin')

@section('title', 'گزارشات مدیریتی')

@section('content')
    <div class="container px-6 mx-auto">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold flex items-center">
                <svg class="w-6 h-6 ml-2 text-blue-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21.21 15.89A10 10 0 1 1 8 2.83"></path>
                    <path d="M22 12A10 10 0 0 0 12 2v10z"></path>
                </svg>
                گزارشات مدیریتی
            </h1>

            <div class="flex gap-2">
                @permission('export-reports')
                <a href="{{ route('admin.reports.export', ['format' => 'pdf', 'report_type' => 'daily']) }}"
                   id="pdf-export-link"
                   class="inline-flex items-center px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors">
                    <svg class="w-5 h-5 ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="12" y1="18" x2="12" y2="12"></line>
                        <line x1="9" y1="15" x2="15" y2="15"></line>
                    </svg>
                    خروجی PDF
                </a>

                <a href="{{ route('admin.reports.export', ['format' => 'excel', 'report_type' => 'daily']) }}"
                   id="excel-export-link"
                   class="inline-flex items-center px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors">
                    <svg class="w-5 h-5 ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10 9 9 9 8 9"></polyline>
                    </svg>
                    خروجی Excel
                </a>
                @endpermission

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
            <div class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition-all duration-300 cursor-pointer" onclick="selectReportType('daily')" id="daily-report-card">
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
                        <p class="text-gray-500 text-sm">گزارش روزانه</p>
                        <h2 class="text-xl font-bold text-gray-700">نمای روزانه</h2>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition-all duration-300 cursor-pointer" onclick="selectReportType('weekly')" id="weekly-report-card">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600">
                        <svg class="w-8 h-8" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                            <polyline points="2 17 12 22 22 17"></polyline>
                            <polyline points="2 12 12 17 22 12"></polyline>
                        </svg>
                    </div>
                    <div class="mr-4">
                        <p class="text-gray-500 text-sm">گزارش هفتگی</p>
                        <h2 class="text-xl font-bold text-gray-700">نمای هفتگی</h2>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition-all duration-300 cursor-pointer" onclick="selectReportType('monthly')" id="monthly-report-card">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                        <svg class="w-8 h-8" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
                        </svg>
                    </div>
                    <div class="mr-4">
                        <p class="text-gray-500 text-sm">گزارش ماهانه</p>
                        <h2 class="text-xl font-bold text-gray-700">نمای ماهانه</h2>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition-all duration-300 cursor-pointer" onclick="selectReportType('custom')" id="custom-report-card">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                        <svg class="w-8 h-8" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                    </div>
                    <div class="mr-4">
                        <p class="text-gray-500 text-sm">گزارش سفارشی</p>
                        <h2 class="text-xl font-bold text-gray-700">بازه دلخواه</h2>
                    </div>
                </div>
            </div>
        </div>

        <div id="date-picker-section" class="bg-white rounded-lg shadow-md p-6 mb-6 hidden">
            <h2 class="text-lg font-semibold mb-4">انتخاب بازه زمانی</h2>
            <div class="flex flex-col md:flex-row gap-4">
                <div class="flex-1">
                    <label for="start-date" class="block mb-2 text-sm font-medium text-gray-700">از تاریخ</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                            <svg class="w-5 h-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                <line x1="8" y1="2" x2="8" y2="6"></line>
                                <line x1="3" y1="10" x2="21" y2="10"></line>
                            </svg>
                        </div>
                        <input type="text" id="start-date" class="border border-gray-300 rounded-lg px-4 py-2 pr-10 w-full focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" readonly>
                    </div>
                </div>

                <div class="flex-1">
                    <label for="end-date" class="block mb-2 text-sm font-medium text-gray-700">تا تاریخ</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                            <svg class="w-5 h-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                <line x1="8" y1="2" x2="8" y2="6"></line>
                                <line x1="3" y1="10" x2="21" y2="10"></line>
                            </svg>
                        </div>
                        <input type="text" id="end-date" class="border border-gray-300 rounded-lg px-4 py-2 pr-10 w-full focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" readonly>
                    </div>
                </div>

                <div class="flex items-end">
                    <button id="apply-date-range" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg transition-colors flex items-center">
                        <svg class="w-5 h-5 ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="9 11 12 14 22 4"></polyline>
                            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                        </svg>
                        اعمال
                    </button>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-lg hover:shadow-xl transition-shadow duration-300 overflow-hidden">
            <div class="p-6">
                <div id="reports-panel"
                     class="fade-in"
                     data-base-url="{{ url('/') }}"
                     data-routes="{{ json_encode([
                            'revenueData' => '/admin/reports/revenue',
                            'dailyRevenue' => '/admin/reports/daily',
                            'weeklyRevenue' => '/admin/reports/weekly',
                            'monthlyRevenue' => '/admin/reports/monthly',
                            'financialData' => '/admin/reports/financial',
                            'specialistsData' => '/admin/reports/specialist-performance',
                            'customersData' => '/admin/reports/customer-satisfaction',
                            'servicesData' => '/admin/reports/popular-services',
                            'export' => '/admin/reports/export'
                        ]) }}"
                >
                    <div class="flex justify-center items-center min-h-[400px]">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
                        <span class="mr-2 text-gray-500">در حال بارگذاری...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    @vite('resources/css/app.css')
    <style>
        .tab-button {
            @apply relative min-w-0 flex-1 overflow-hidden py-4 px-4 text-center text-sm font-medium text-gray-500 hover:text-gray-700 hover:bg-gray-50 focus:z-10 focus:outline-none transition-colors;
        }

        .tab-button.active {
            @apply text-blue-600 border-b-2 border-blue-500;
        }

        .report-type-card.active {
            @apply border-2 border-blue-500 ring-2 ring-blue-200;
        }
    </style>
@endpush

@push('scripts')
    <script>
        window.initialData = {
            baseUrl: '{{ url('/') }}',
            routes: {
                dailyRevenue: '/admin/reports/daily',
                weeklyRevenue: '/admin/reports/weekly',
                monthlyRevenue: '/admin/reports/monthly',
                specialists: '/admin/reports/specialist-performance',
                financial: '/admin/reports/financial',
                customers: '/admin/reports/customer-satisfaction',
                services: '/admin/reports/popular-services',
                export: '/admin/reports/export'
            },
            dateFormat: 'jYYYY/jMM/jDD'
        };

            document.addEventListener('DOMContentLoaded', function() {
            const tabButtons = document.querySelectorAll('.tab-button');
            let activeReportType = 'daily';
            let startDate = '';
            let endDate = '';

            function updateExportLinks() {
            let params = new URLSearchParams();
            params.append('report_type', activeReportType);

            if (startDate && endDate) {
            params.append('start_date', startDate);
            params.append('end_date', endDate);
        }

            let pdfLink = document.getElementById('pdf-export-link');
            if (pdfLink) {
            let pdfBaseUrl = "{{ route('admin.reports.export') }}";
            params.set('format', 'pdf');
            pdfLink.href = `${pdfBaseUrl}?${params.toString()}`;
        }

            let excelLink = document.getElementById('excel-export-link');
            if (excelLink) {
            let excelBaseUrl = "{{ route('admin.reports.export') }}";
            params.set('format', 'excel');
            excelLink.href = `${excelBaseUrl}?${params.toString()}`;
        }
        }

            tabButtons.forEach(button => {
            button.addEventListener('click', function() {
            tabButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            const tabName = this.getAttribute('data-tab');
            console.log('Selected tab:', tabName);
        });
        });

            $('#start-date, #end-date').persianDatepicker({
            format: 'YYYY/MM/DD',
            autoClose: true,
            onSelect: function(unix) {
            const input = this.model.inputElement;
            if (input.id === 'start-date') {
            startDate = new Date(unix).toISOString().split('T')[0];
        } else if (input.id === 'end-date') {
            endDate = new Date(unix).toISOString().split('T')[0];
        }
            updateExportLinks();
        }
        });

            window.selectReportType = function(type) {
            const reportCards = document.querySelectorAll('[id$="-report-card"]');
            reportCards.forEach(card => {
            card.classList.remove('ring-2', 'ring-blue-200', 'border-2', 'border-blue-500');
        });

            document.getElementById(type + '-report-card').classList.add('ring-2', 'ring-blue-200', 'border-2', 'border-blue-500');

            const datePickerSection = document.getElementById('date-picker-section');
            if(type === 'custom') {
            datePickerSection.classList.remove('hidden');
        } else {
            datePickerSection.classList.add('hidden');
            console.log('Selected report type:', type);
        }

            activeReportType = type;

            updateExportLinks();
        };

            document.getElementById('apply-date-range').addEventListener('click', function() {
            const startDateInput = document.getElementById('start-date').value;
            const endDateInput = document.getElementById('end-date').value;

            if(startDateInput && endDateInput) {
            console.log('Custom date range:', startDateInput, 'to', endDateInput);

            updateExportLinks();
        } else {
            alert('لطفاً بازه زمانی را انتخاب کنید');
        }
        });

            updateExportLinks();

            selectReportType('daily');
        });
    </script>
    @viteReactRefresh
    @vite(['resources/js/admin.jsx'])
@endpush
