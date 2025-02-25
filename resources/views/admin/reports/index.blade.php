@extends('layouts.admin')

@section('title', 'گزارشات مدیریتی')

@section('content')
    <div id="reports-panel"
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
    <!-- Fallback loading spinner -->
    <div class="flex justify-center items-center min-h-[400px]">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
    </div>
    </div>
@endsection

@push('styles')
    @vite('resources/css/app.css')
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
    </script>
    @viteReactRefresh
    @vite(['resources/js/admin.jsx'])
@endpush
