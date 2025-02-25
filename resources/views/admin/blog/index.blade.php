@extends('layouts.admin')

@section('title', 'مدیریت وبلاگ')

@section('content')
    <div id="admin-blog">
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
                posts: '{{ route('api.blog.posts.index') }}',
                categories: '{{ route('api.blog.categories.index') }}'
            }
        };
    </script>
    @viteReactRefresh
    @vite(['resources/js/admin.jsx'])
@endpush
