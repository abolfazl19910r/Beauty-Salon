@extends('layouts.admin')
@section('title', 'مدیریت اطلاعیه‌ها')

@section('content')
    <div class="fade-in">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-5">
            <div>
                <h1 class="text-xl font-bold flex items-center gap-2" style="color:var(--admin-text);">
                    <svg class="w-5 h-5" style="color:var(--admin-accent);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="16" y1="13" x2="8" y2="13"/>
                        <line x1="16" y1="17" x2="8" y2="17"/>
                        <polyline points="10 9 9 9 8 9"/>
                    </svg>
                    مدیریت اطلاعیه‌ها
                </h1>
                <p class="text-sm mt-0.5" style="color:var(--admin-text-dim);">اطلاعیه‌های سیستم برای کاربران</p>
            </div>
        </div>

        <div class="rounded-xl overflow-hidden" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
            <div id="admin-announcements" class="min-h-64">
                <div class="flex items-center justify-center gap-3 py-16" style="color:var(--admin-text-dim);">
                    <div class="w-6 h-6 rounded-full border-2 animate-spin"
                         style="border-color:var(--admin-accent); border-top-color:transparent;"></div>
                    <span class="text-sm">در حال بارگذاری...</span>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/css/persian-datepicker.min.css">
    <style>
        .pwt-btn-today, .pwt-btn-submit {
            background-color: var(--admin-accent) !important;
            color: white !important;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/persian-date@1.1.0/dist/persian-date.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/js/persian-datepicker.min.js"></script>
    @viteReactRefresh
    @vite(['resources/js/admin.jsx'])
@endpush
