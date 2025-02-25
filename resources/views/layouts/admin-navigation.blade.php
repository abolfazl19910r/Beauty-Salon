<nav class="w-64 min-h-screen bg-gray-800 text-white">
    <div class="p-4">
        <h2 class="text-xl font-bold">پنل مدیریت</h2>
    </div>
    <nav class="mt-4">
        <x-admin-nav-link href="{{ route('admin.dashboard') }}" :active="request()->routeIs('admin.dashboard')">
            داشبورد
        </x-admin-nav-link>

        <x-admin-nav-link href="{{ route('admin.reports.index') }}" :active="request()->routeIs('admin.reports.*')">
            گزارشات
        </x-admin-nav-link>

        <x-admin-nav-link href="{{ route('admin.announcements') }}" :active="request()->routeIs('admin.announcements')">
            اعلانات
        </x-admin-nav-link>

        <x-admin-nav-link href="{{ route('admin.blog') }}" :active="request()->routeIs('admin.blog')">
            وبلاگ
        </x-admin-nav-link>

        <x-admin-nav-link href="{{ route('admin.gallery') }}" :active="request()->routeIs('admin.gallery')">
            گالری
        </x-admin-nav-link>

        <x-admin-nav-link href="{{ route('admin.loyalty') }}" :active="request()->routeIs('admin.loyalty')">
            امتیازات
        </x-admin-nav-link>

        <x-admin-nav-link href="{{ route('admin.schedule') }}" :active="request()->routeIs('admin.schedule')">
            زمانبندی
        </x-admin-nav-link>
    </nav>
</nav>
