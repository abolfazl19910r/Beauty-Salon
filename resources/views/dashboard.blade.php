<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight bg-gradient-to-r from-pink-500 to-purple-600 bg-clip-text text-transparent">
                {{ __('داشبورد مدیریت') }}
            </h2>
            <div class="flex space-x-2 space-x-reverse">
                <button id="refresh-dashboard" class="bg-gray-200 hover:bg-gray-300 p-2 rounded-full transition-colors">
                    <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                </button>
            </div>
        </div>
    </x-slot>

    <div id="admin-dashboard" class="fade-in"></div>

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
</x-app-layout>
