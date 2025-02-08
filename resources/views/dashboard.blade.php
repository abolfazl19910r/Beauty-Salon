<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div id="admin-dashboard"></div>

    @push('scripts')
        @viteReactRefresh
        @vite(['resources/js/Components/Admin/AdminDashboard.jsx'])
    @endpush
</x-app-layout>
