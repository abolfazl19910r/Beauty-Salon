@extends('layouts.app')

@section('title', 'داشبورد')

@section('content')
    <div class="fade-in">

        {{-- هدر --}}
        <div class="flex items-center justify-between mb-8">
            <div>
                <p class="text-xs font-semibold text-[#C9A24B] tracking-[0.3em] uppercase mb-1">خوش آمدید</p>
                <h1 class="text-2xl md:text-3xl font-bold text-[#E6CD8A]"
                    style="font-family:'Noto Naskh Arabic','Vazirmatn',serif">
                    داشبورد مدیریت
                </h1>
            </div>
            <button id="refresh-dashboard"
                    class="w-10 h-10 rounded-xl bg-[#2E2117] border border-[#C9A24B]/15
                       flex items-center justify-center text-[#F8F3E9]/60
                       hover:text-[#E6CD8A] hover:border-[#C9A24B]/40 transition-colors"
                    title="بروزرسانی">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
            </button>
        </div>

        {{-- کامپوننت React (دست‌نخورده) --}}
        <div id="admin-dashboard"></div>

    </div>
@endsection

@push('scripts')
    @viteReactRefresh
    @vite(['resources/js/Components/Admin/AdminDashboard.jsx'])
    <script>
        document.getElementById('refresh-dashboard').addEventListener('click', function() {
            if (window.refreshDashboard && typeof window.refreshDashboard === 'function') {
                window.refreshDashboard();
            }
        });
    </script>
@endpush
