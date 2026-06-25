@extends('layouts.admin')
@section('title', 'مدیریت وبلاگ')

@section('content')
    <div class="fade-in">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-5">
            <div>
                <h1 class="text-xl font-bold flex items-center gap-2" style="color:var(--admin-text);">
                    <svg class="w-5 h-5" style="color:var(--admin-accent);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path d="M4 19.5A2.5 2.5 0 016.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/>
                    </svg>
                    مدیریت وبلاگ
                </h1>
                <p class="text-sm mt-0.5" style="color:var(--admin-text-dim);">مدیریت مقالات وبلاگ سالن</p>
            </div>
            <a href="{{ route('admin.blog.create') }}"
               class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium text-white"
               style="background:var(--admin-accent);"
               onmouseover="this.style.background='var(--admin-accent-hover)'"
               onmouseout="this.style.background='var(--admin-accent)'">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                مقاله جدید
            </a>
        </div>

        {{-- React mount point --}}
        <div class="rounded-xl overflow-hidden" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
            <div id="admin-blog" class="min-h-64">
                <div class="flex items-center justify-center gap-3 py-16" style="color:var(--admin-text-dim);">
                    <div class="w-6 h-6 rounded-full border-2 border-t-transparent animate-spin"
                         style="border-color:var(--admin-accent); border-top-color:transparent;"></div>
                    <span class="text-sm">در حال بارگذاری...</span>
                </div>
            </div>
        </div>
    </div>
@endsection

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
