@extends('layouts.admin')

@section('title', 'مدیریت گالری')

@section('content')
    <div id="admin-gallery">
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
                images: '{{ route('api.gallery.index') }}',
                upload: '{{ route('api.gallery.store') }}',
                reorder: '{{ route('api.gallery.reorder') }}'
            }
        };
    </script>
    @viteReactRefresh
    @vite(['resources/js/admin.jsx'])
@endpush
