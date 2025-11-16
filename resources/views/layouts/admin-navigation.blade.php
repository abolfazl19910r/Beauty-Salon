<nav class="w-64 min-h-screen bg-white shadow-lg border-l overflow-y-auto">
    <div class="p-4 border-b border-gray-100">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-bold flex items-center">
                <svg class="w-8 h-8 ml-2 text-pink-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="8.5" cy="7" r="4"></circle>
                    <path d="M20 8v6M23 11h-6"></path>
                </svg>
                <span class="bg-gradient-to-r from-pink-500 to-purple-600 bg-clip-text text-transparent">پنل مدیریت</span>
            </h2>
        </div>
        <p class="text-xs text-gray-500 mt-2">سیستم مدیریت سالن زیبایی</p>
    </div>

    <nav class="mt-4 px-2">
        <div class="py-2">
            <h3 class="text-xs font-semibold text-gray-400 px-3 mb-2 uppercase">داشبورد</h3>
            <x-admin-nav-link href="{{ route('admin.dashboard') }}" :active="request()->routeIs('admin.dashboard')"
                              class="flex items-center px-3 py-2.5 mb-1 text-sm font-medium rounded-lg transition-colors">
                <svg class="w-5 h-5 ml-2 opacity-75" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="7" height="7"></rect>
                    <rect x="14" y="3" width="7" height="7"></rect>
                    <rect x="14" y="14" width="7" height="7"></rect>
                    <rect x="3" y="14" width="7" height="7"></rect>
                </svg>
                داشبورد
            </x-admin-nav-link>

            <x-admin-nav-link href="{{ route('admin.reports.index') }}" :active="request()->routeIs('admin.reports.*')"
                              class="flex items-center px-3 py-2.5 mb-1 text-sm font-medium rounded-lg transition-colors">
                <svg class="w-5 h-5 ml-2 opacity-75" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21.21 15.89A10 10 0 1 1 8 2.83"></path>
                    <path d="M22 12A10 10 0 0 0 12 2v10z"></path>
                </svg>
                گزارشات
            </x-admin-nav-link>
        </div>

        <div class="py-2">
            <h3 class="text-xs font-semibold text-gray-400 px-3 mb-2 uppercase">سیستم‌ها</h3>
            <x-admin-nav-link href="{{ route('admin.announcements') }}" :active="request()->routeIs('admin.announcements')"
                              class="flex items-center px-3 py-2.5 mb-1 text-sm font-medium rounded-lg transition-colors">
                <svg class="w-5 h-5 ml-2 opacity-75" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                </svg>
                اطلاعیه ها
            </x-admin-nav-link>

            <x-admin-nav-link href="{{ route('admin.notifications.index') }}" :active="request()->routeIs('admin.notifications.*')"
                              class="flex items-center px-3 py-2.5 mb-1 text-sm font-medium rounded-lg transition-colors">
                <svg class="w-5 h-5 ml-2 opacity-75" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                </svg>
                اعلانات
            </x-admin-nav-link>

            <x-admin-nav-link href="{{ route('admin.blog') }}" :active="request()->routeIs('admin.blog')"
                              class="flex items-center px-3 py-2.5 mb-1 text-sm font-medium rounded-lg transition-colors">
                <svg class="w-5 h-5 ml-2 opacity-75" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                </svg>
                وبلاگ
            </x-admin-nav-link>

            <x-admin-nav-link href="{{ route('admin.gallery') }}" :active="request()->routeIs('admin.gallery')"
                              class="flex items-center px-3 py-2.5 mb-1 text-sm font-medium rounded-lg transition-colors">
                <svg class="w-5 h-5 ml-2 opacity-75" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                    <circle cx="8.5" cy="8.5" r="1.5"></circle>
                    <polyline points="21 15 16 10 5 21"></polyline>
                </svg>
                گالری
            </x-admin-nav-link>

            <x-admin-nav-link href="{{ route('admin.loyalty') }}" :active="request()->routeIs('admin.loyalty')"
                              class="flex items-center px-3 py-2.5 mb-1 text-sm font-medium rounded-lg transition-colors">
                <svg class="w-5 h-5 ml-2 opacity-75" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                </svg>
                امتیازات
            </x-admin-nav-link>

            <x-admin-nav-link href="{{ route('admin.schedule') }}" :active="request()->routeIs('admin.schedule')"
                              class="flex items-center px-3 py-2.5 mb-1 text-sm font-medium rounded-lg transition-colors">
                <svg class="w-5 h-5 ml-2 opacity-75" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
                زمانبندی
            </x-admin-nav-link>
        </div>

        <div class="py-2">
            <h3 class="text-xs font-semibold text-gray-400 px-3 mb-2 uppercase">مدیریت کاربران</h3>
            <x-admin-nav-link href="{{ route('admin.users.index') }}" :active="request()->routeIs('admin.users.*')"
                              class="flex items-center px-3 py-2.5 mb-1 text-sm font-medium rounded-lg transition-colors">
                <svg class="w-5 h-5 ml-2 opacity-75" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                کاربران
            </x-admin-nav-link>

            <x-admin-nav-link href="{{ route('admin.roles.index') }}" :active="request()->routeIs('admin.roles.*')"
                              class="flex items-center px-3 py-2.5 mb-1 text-sm font-medium rounded-lg transition-colors">
                <svg class="w-5 h-5 ml-2 opacity-75" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 11h6m-3-3v6M17 3a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h10z"></path>
                </svg>
                نقش‌ها
            </x-admin-nav-link>
        </div>
    </nav>
</nav>
