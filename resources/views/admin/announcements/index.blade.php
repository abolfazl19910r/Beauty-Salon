@extends('layouts.admin')

@section('title', 'مدیریت اعلانات')

@section('content')
    <div id="admin-announcements">
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
            routes: {
                list: '{{ route('api.announcements.index') }}',
                create: '{{ route('api.announcements.store') }}',
                update: '{{ route('api.announcements.update', ':id') }}',
                delete: '{{ route('api.announcements.destroy', ':id') }}'
            }
        };
    </script>
    @viteReactRefresh
    @vite(['resources/js/admin.jsx'])
@endpush
