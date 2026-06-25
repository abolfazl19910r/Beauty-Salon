@extends('layouts.admin')
@section('title', 'مدیریت گالری')

@section('content')
    <div class="fade-in">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-5">
            <div>
                <h1 class="text-xl font-bold flex items-center gap-2" style="color:var(--admin-text);">
                    <svg class="w-5 h-5" style="color:var(--admin-accent);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                        <circle cx="8.5" cy="8.5" r="1.5"/>
                        <polyline points="21 15 16 10 5 21"/>
                    </svg>
                    مدیریت گالری
                </h1>
                <p class="text-sm mt-0.5" style="color:var(--admin-text-dim);">تصاویر نمونه کارهای سالن</p>
            </div>
        </div>

        <div class="rounded-xl overflow-hidden" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
            <div id="admin-gallery" class="min-h-64">
                <div class="flex items-center justify-center gap-3 py-16" style="color:var(--admin-text-dim);">
                    <div class="w-6 h-6 rounded-full border-2 animate-spin"
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
                images:  '{{ route('admin.gallery.images') }}',
                upload:  '{{ route('admin.gallery.store') }}',
                reorder: '{{ route('admin.gallery.reorder') }}',
                stats:   '{{ route('admin.gallery.stats') }}'
            }
        };
    </script>
    @viteReactRefresh
    @vite(['resources/js/admin.jsx'])
@endpush
