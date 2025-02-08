@extends('layouts.app')

@section('title', 'گزارش‌های مدیریتی')

@section('content')
    <div id="reports-app"></div>

    @vite(['resources/js/reports.jsx'])

    @push('scripts')
        <script>
            window.initialData = {
                baseUrl: '{{ url('/') }}',
                routes: {
                    daily: '{{ route('admin.reports.daily') }}',
                    weekly: '{{ route('admin.reports.weekly') }}',
                    monthly: '{{ route('admin.reports.monthly') }}',
                    specialists: '{{ route('admin.reports.specialists') }}',
                    satisfaction: '{{ route('admin.reports.satisfaction') }}',
                    services: '{{ route('admin.reports.services') }}'
                }
            };
        </script>
    @endpush
@endsection
