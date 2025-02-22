@extends('layouts.admin')

@section('title', 'گزارش‌های مدیریتی')

@section('content')
    <div id="reports-panel" class="p-6">
        {{-- Add a loading state that will be replaced by React --}}
        <div class="text-center">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900"></div>
            <div class="mt-2">در حال بارگذاری...</div>
        </div>
    </div>

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
                    services: '{{ route('admin.reports.services') }}',
                    export: '{{ route('admin.reports.export') }}'
                }
            };
        </script>
    @endpush
@endsection
