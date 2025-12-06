<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl" class="rtl">
<head>
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazir-font@v30.1.0/dist/font-face.css" rel="stylesheet" type="text/css" />
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') | پنل مدیریت سالن زیبایی</title>

    <link rel="stylesheet" href="https://unpkg.com/persian-datepicker@latest/dist/css/persian-datepicker.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
    @stack('styles')
    <style>
        .persian-number {
            -moz-font-feature-settings: "ss02";
            -webkit-font-feature-settings: "ss02";
            font-feature-settings: "ss02";
        }

        .sidebar-active {
            background: linear-gradient(90deg, rgba(37, 99, 235, 0.1) 0%, rgba(37, 99, 235, 0.5) 100%);
            border-right: 3px solid #2563eb;
        }

        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(243, 244, 246, 0.8);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: rgba(156, 163, 175, 0.5);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: rgba(107, 114, 128, 0.7);
        }

        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .hover-shadow-lg {
            transition: box-shadow 0.3s, transform 0.3s;
        }

        .hover-shadow-lg:hover {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            transform: translateY(-2px);
        }

        .notification-badge {
            position: absolute;
            top: -4px;
            right: -4px;
            min-width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: bold;
            padding: 0 4px;
            border-radius: 9px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }

        .notification-item-unread {
            background: linear-gradient(90deg, rgba(59, 130, 246, 0.05) 0%, rgba(59, 130, 246, 0.1) 100%);
            border-right: 3px solid #3b82f6;
        }
    </style>
</head>
<body class="bg-gray-50 rtl font-vazir text-gray-800">
<div class="flex h-screen overflow-hidden">
    <!-- Sidebar -->
    <aside class="w-64 min-h-screen bg-white shadow-lg border-l overflow-y-auto hidden md:block">
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
                <a href="{{ route('admin.dashboard') }}"
                   class="flex items-center px-3 py-2.5 mb-1 text-sm font-medium rounded-lg {{ request()->routeIs('admin.dashboard') ? 'sidebar-active text-blue-600' : 'text-gray-700 hover:bg-gray-100' }} transition-colors">
                    <svg class="w-5 h-5 ml-2 opacity-75" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="7" height="7"></rect>
                        <rect x="14" y="3" width="7" height="7"></rect>
                        <rect x="14" y="14" width="7" height="7"></rect>
                        <rect x="3" y="14" width="7" height="7"></rect>
                    </svg>
                    داشبورد
                </a>

                @permission('view-reports')
                <a href="{{ route('admin.reports.index') }}"
                   class="flex items-center px-3 py-2.5 mb-1 text-sm font-medium rounded-lg {{ request()->routeIs('admin.reports*') ? 'sidebar-active text-blue-600' : 'text-gray-700 hover:bg-gray-100' }} transition-colors">
                    <svg class="w-5 h-5 ml-2 opacity-75" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21.21 15.89A10 10 0 1 1 8 2.83"></path>
                        <path d="M22 12A10 10 0 0 0 12 2v10z"></path>
                    </svg>
                    گزارشات
                </a>
                @endpermission
            </div>

            <div class="py-2">
                <h3 class="text-xs font-semibold text-gray-400 px-3 mb-2 uppercase">مدیریت</h3>
                @permission('view-services')
                <a href="{{ route('admin.services.index') }}"
                   class="flex items-center px-3 py-2.5 mb-1 text-sm font-medium rounded-lg {{ request()->routeIs('admin.services*') ? 'sidebar-active text-blue-600' : 'text-gray-700 hover:bg-gray-100' }} transition-colors">
                    <svg class="w-5 h-5 ml-2 opacity-75" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                    خدمات
                </a>
                @endpermission

                @permission('view-specialists')
                <a href="{{ route('admin.specialists.index') }}"
                   class="flex items-center px-3 py-2.5 mb-1 text-sm font-medium rounded-lg {{ request()->routeIs('admin.specialists*') ? 'sidebar-active text-blue-600' : 'text-gray-700 hover:bg-gray-100' }} transition-colors">
                    <svg class="w-5 h-5 ml-2 opacity-75" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    متخصصین
                </a>
                @endpermission

                @permission('view-bookings')
                <a href="{{ route('admin.bookings.index') }}"
                   class="flex items-center px-3 py-2.5 mb-1 text-sm font-medium rounded-lg {{ request()->routeIs('admin.bookings*') ? 'sidebar-active text-blue-600' : 'text-gray-700 hover:bg-gray-100' }} transition-colors">
                    <svg class="w-5 h-5 ml-2 opacity-75" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    نوبت‌ها
                </a>
                @endpermission

                @permission('view-categories')
                <a href="{{ route('admin.categories.index') }}"
                   class="flex items-center px-3 py-2.5 mb-1 text-sm font-medium rounded-lg {{ request()->routeIs('admin.categories*') ? 'sidebar-active text-blue-600' : 'text-gray-700 hover:bg-gray-100' }} transition-colors">
                    <svg class="w-5 h-5 ml-2 opacity-75" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                        <polyline points="2 17 12 22 22 17"></polyline>
                        <polyline points="2 12 12 17 22 12"></polyline>
                    </svg>
                    دسته‌بندی‌ها
                </a>
                @endpermission
            </div>

            <div class="py-2">
                <h3 class="text-xs font-semibold text-gray-400 px-3 mb-2 uppercase">سیستم‌ها</h3>

                <a href="{{ route('admin.notifications.index') }}"
                   class="flex items-center px-3 py-2.5 mb-1 text-sm font-medium rounded-lg {{ request()->routeIs('admin.notifications*') ? 'sidebar-active text-blue-600' : 'text-gray-700 hover:bg-gray-100' }} transition-colors justify-between">
                    <span class="flex items-center">
                        <svg class="w-5 h-5 ml-2 opacity-75" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                        </svg>
                        اعلانات
                    </span>
                    <span id="sidebar-notification-count" class="mr-2 px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 hidden">
                    </span>
                </a>

                @permission('view-loyalty')
                <a href="{{ route('admin.loyalty.index') }}"
                   class="flex items-center px-3 py-2.5 mb-1 text-sm font-medium rounded-lg {{ request()->routeIs('admin.loyalty*') ? 'sidebar-active text-blue-600' : 'text-gray-700 hover:bg-gray-100' }} transition-colors">
                    <svg class="w-5 h-5 ml-2 opacity-75" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                    </svg>
                    امتیازات
                </a>
                @endpermission

                @permission('view-blog')
                <a href="{{ route('admin.blog.index') }}"
                   class="flex items-center px-3 py-2.5 mb-1 text-sm font-medium rounded-lg {{ request()->routeIs('admin.blog*') ? 'sidebar-active text-blue-600' : 'text-gray-700 hover:bg-gray-100' }} transition-colors">
                    <svg class="w-5 h-5 ml-2 opacity-75" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                    </svg>
                    وبلاگ
                </a>
                @endpermission

                @permission('view-gallery')
                <a href="{{ route('admin.gallery.index') }}"
                   class="flex items-center px-3 py-2.5 mb-1 text-sm font-medium rounded-lg {{ request()->routeIs('admin.gallery*') ? 'sidebar-active text-blue-600' : 'text-gray-700 hover:bg-gray-100' }} transition-colors">
                    <svg class="w-5 h-5 ml-2 opacity-75" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                        <circle cx="8.5" cy="8.5" r="1.5"></circle>
                        <polyline points="21 15 16 10 5 21"></polyline>
                    </svg>
                    گالری
                </a>
                @endpermission

                @permission('view-announcements')
                <a href="{{ route('admin.announcements.index') }}"
                   class="flex items-center px-3 py-2.5 mb-1 text-sm font-medium rounded-lg {{ request()->routeIs('admin.announcements*') ? 'sidebar-active text-blue-600' : 'text-gray-700 hover:bg-gray-100' }} transition-colors">
                    <svg class="w-5 h-5 ml-2 opacity-75" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10 9 9 9 8 9"></polyline>
                    </svg>
                    اطلاعیه
                </a>
                @endpermission
            </div>

            <div class="py-2">
                <h3 class="text-xs font-semibold text-gray-400 px-3 mb-2 uppercase">مدیریت کاربران</h3>
                @permission('view-users')
                <a href="{{ route('admin.users.index') }}"
                   class="flex items-center px-3 py-2.5 mb-1 text-sm font-medium rounded-lg {{ request()->routeIs('admin.users*') ? 'sidebar-active text-blue-600' : 'text-gray-700 hover:bg-gray-100' }} transition-colors">
                    <svg class="w-5 h-5 ml-2 opacity-75" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                    کاربران
                </a>
                @endpermission

                @permission('manage-roles')
                <a href="{{ route('admin.permissions.index') }}"
                   class="flex items-center px-3 py-2.5 mb-1 text-sm font-medium rounded-lg {{ request()->routeIs('admin.permissions*') ? 'sidebar-active text-blue-600' : 'text-gray-700 hover:bg-gray-100' }} transition-colors">
                    <svg class="w-5 h-5 ml-2 opacity-75" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    دسترسی‌ها
                </a>
                @endpermission

                @permission('manage-roles')
                <a href="{{ route('admin.roles.index') }}"
                   class="flex items-center px-3 py-2.5 mb-1 text-sm font-medium rounded-lg {{ request()->routeIs('admin.roles*') ? 'sidebar-active text-blue-600' : 'text-gray-700 hover:bg-gray-100' }} transition-colors">
                    <svg class="w-5 h-5 ml-2 opacity-75" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 11h6m-3-3v6M17 3a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h10z"></path>
                    </svg>
                    نقش‌ها
                </a>
                @endpermission
            </div>

            <div class="py-2">
                <h3 class="text-xs font-semibold text-gray-400 px-3 mb-2 uppercase">تنظیمات</h3>
                <a href="{{ route('admin.profile.edit') }}"
                   class="flex items-center px-3 py-2.5 mb-1 text-sm font-medium rounded-lg {{ request()->routeIs('admin.profile*') ? 'sidebar-active text-blue-600' : 'text-gray-700 hover:bg-gray-100' }} transition-colors">
                    <svg class="w-5 h-5 ml-2 opacity-75" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    پروفایل
                </a>

                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <button type="submit" class="w-full flex items-center px-3 py-2.5 mb-1 text-sm font-medium rounded-lg text-gray-700 hover:bg-red-50 hover:text-red-600 transition-colors text-right">
                        <svg class="w-5 h-5 ml-2 opacity-75" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            <polyline points="16 17 21 12 16 7"></polyline>
                            <line x1="21" y1="12" x2="9" y2="12"></line>
                        </svg>
                        خروج
                    </button>
                </form>
            </div>
        </nav>
    </aside>

    <div id="sidebar-backdrop" class="md:hidden fixed inset-0 bg-gray-900 bg-opacity-50 z-40 hidden" onclick="toggleSidebar()"></div>

    <div class="flex-1 flex flex-col relative">
        <header class="bg-white border-b shadow-sm sticky top-0 z-30">
            <div class="px-4 py-3 flex justify-between items-center">
                <button type="button" class="md:hidden text-gray-600 focus:outline-none" onclick="toggleSidebar()">
                    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="3" y1="12" x2="21" y2="12"></line>
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <line x1="3" y1="18" x2="21" y2="18"></line>
                    </svg>
                </button>

                <h1 class="text-lg font-bold md:hidden">@yield('title')</h1>

                <div class="flex items-center space-x-3 space-x-reverse">
                    <button type="button"
                            id="search-button"
                            class="p-1 rounded-full text-gray-600 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                            onclick="toggleSearchModal()">
                        <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                    </button>

                    <div class="relative inline-block text-left">
                        <button type="button"
                                id="notification-button"
                                class="p-1 rounded-full text-gray-600 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 relative"
                                aria-expanded="false" aria-haspopup="true">
                            <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                                <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                            </svg>
                            {{-- عدد فعلی را 0 کنید و در جاوااسکریپت پنهانش می‌کنیم --}}
                            <span id="notification-count" class="absolute top-0 right-0 hidden items-center justify-center px-2 py-1 text-xs font-bold leading-none text-red-100 bg-red-500 rounded-full">0</span>
                        </button>

                        <div id="notification-dropdown"
                             class="hidden absolute left-0 z-50 mt-2 w-72 origin-top-left rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
                             role="menu" aria-orientation="vertical" aria-labelledby="notification-button" tabindex="-1">
                            <div class="px-4 py-2 border-b">
                                <p class="text-sm font-bold text-gray-900">اعلانات جدید</p>
                            </div>
                            <div id="notification-list-container" class="py-1 max-h-64 overflow-y-auto" role="none">
                                <p id="loading-notifications-message" class="px-4 py-2 text-sm text-gray-500">در حال بارگذاری...</p>
                                <p id="no-notifications-message" class="px-4 py-2 text-sm text-gray-500 hidden">اعلانی برای نمایش وجود ندارد.</p>
                            </div>
                            <div class="border-t border-gray-100">
                                <a href="{{ route('admin.notifications.index') }}"
                                   class="w-full text-center text-blue-600 hover:bg-blue-50 block px-4 py-2 text-sm"
                                   role="menuitem"
                                   tabindex="-1">
                                    مشاهده همه اعلانات
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="relative inline-block text-left">
                        <button type="button" class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-gray-100 hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" id="user-menu-button" aria-expanded="false" aria-haspopup="true">
                            <span class="text-sm font-medium">
                                {{ mb_substr(optional(auth()->user())->name ?? 'کاربر', 0, 1) }}
                            </span>
                        </button>

                        <div id="user-menu-dropdown"
                             class="hidden absolute left-0 z-50 mt-2 w-56 origin-top-left rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
                             role="menu" aria-orientation="vertical" aria-labelledby="user-menu-button" tabindex="-1">
                            <div class="py-1" role="none">

                                <div class="px-4 py-2 border-b">
                                    <p class="text-sm font-medium text-gray-900 truncate">{{ auth()->user()->name ?? 'کاربر' }}</p>
                                    <p class="text-sm text-gray-500 truncate">{{ auth()->user()->email ?? '' }}</p>
                                </div>

                                <a href="{{ route('admin.profile.show') }}" class="text-gray-700 hover:bg-gray-100 flex items-center px-4 py-2 text-sm" role="menuitem" tabindex="-1" id="user-menu-item-0">
                                    <svg class="w-5 h-5 mr-2 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-6-3a2 2 0 11-4 0 2 2 0 014 0zM8 12a6 6 0 00-5.43 3.59A8 8 0 0118 10a8 8 0 01-4.57 7.59A6 6 0 008 12z" clip-rule="evenodd" />
                                    </svg>
                                    نمایش پروفایل
                                </a>
                                <a href="{{ route('admin.profile.edit') }}" class="text-gray-700 hover:bg-gray-100 flex items-center px-4 py-2 text-sm" role="menuitem" tabindex="-1" id="user-menu-item-1">
                                    <svg class="w-5 h-5 mr-2 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z" />
                                        <path fill-rule="evenodd" d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" clip-rule="evenodd" />
                                    </svg>
                                    ویرایش پروفایل
                                </a>

                                <form method="POST" action="{{ route('logout') }}" role="none" class="border-t border-gray-100">
                                    @csrf
                                    <button type="submit" class="w-full text-left text-red-600 hover:bg-red-50 flex items-center px-4 py-2 text-sm" role="menuitem" tabindex="-1" id="user-menu-item-2">
                                        <svg class="w-5 h-5 mr-2 text-red-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m-3 0l3-3m0 0l-3-3m3 3H9" />
                                        </svg>
                                        خروج
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="hidden md:flex px-4 py-2 border-t bg-gray-50 text-sm">
                <a href="{{ route('admin.dashboard') }}" class="text-blue-600 hover:text-blue-800">پنل مدیریت</a>
                <span class="mx-2 text-gray-400">/</span>
                <span class="text-gray-600">@yield('title')</span>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto bg-gray-50 p-4">
            @if(session('success'))
                <div class="mb-4 bg-green-50 border-r-4 border-green-500 p-4 text-green-800 fade-in rounded-lg shadow-sm" role="alert">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="mr-3">
                            <span>{{ session('success') }}</span>
                        </div>
                    </div>
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 bg-red-50 border-r-4 border-red-500 p-4 text-red-800 fade-in rounded-lg shadow-sm" role="alert">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="mr-3">
                            <span>{{ session('error') }}</span>
                        </div>
                    </div>
                </div>
            @endif

            <div class="fade-in">
                @yield('content')
            </div>
        </main>

        <footer class="bg-white py-3 px-4 text-center text-gray-500 text-xs border-t">
            <p>© {{ date('Y') }} سیستم مدیریت سالن زیبایی. تمامی حقوق محفوظ است.</p>
        </footer>
    </div>
</div>

<div id="search-modal"
     class="fixed inset-0 z-[100] hidden flex items-start justify-center pt-16 bg-gray-900 bg-opacity-50 transition-opacity"
     aria-modal="true" role="dialog"
     onclick="closeSearchModalIfClickOutside(event)">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-lg mx-4 overflow-hidden transform transition-all fade-in-up"
         role="document"
         id="search-modal-content">

        <div class="p-4">
            <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-4">جستجوی سریع در پنل</h3>
            <form action="{{ route('admin.search.index') }}" method="GET">
                <div class="relative">
                    <input type="search" name="q" placeholder="جستجو بین خدمات، متخصصین، کاربران..."
                           class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 text-sm transition"
                           autofocus>
                    <div class="absolute right-3 top-0 bottom-0 flex items-center pointer-events-none text-gray-400">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                    </div>
                </div>
                <div class="mt-4 flex justify-end">
                    <button type="button" onclick="toggleSearchModal()"
                            class="ml-2 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition">
                        انصراف
                    </button>
                    <button type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition">
                        جستجو
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .fade-in-up {
        animation: fadeInUp 0.3s ease-out;
    }

    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://unpkg.com/persian-date/dist/persian-date.min.js"></script>
<script src="https://unpkg.com/persian-datepicker@latest/dist/js/persian-datepicker.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    window.AdminRoutes = {
        notificationsIndex: '{{ route("admin.notifications.index") }}',
        notificationsLatest: '{{ route("admin.notifications.latest") }}',
        notificationsCount: '{{ route("admin.notifications.count") }}',
        // اصلاح شد: استفاده از "000" به عنوان Placeholder امن
        notificationsRead: '{{ route("admin.notifications.read", "000") }}'.replace('000', ':id'),
        csrfToken: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
    };

    function fetchUnreadCount() {
        fetch(window.AdminRoutes.notificationsCount)
            .then(response => response.json())
            .then(data => {
                const countElement = document.getElementById('notification-count');
                const count = data.count;

                if (count > 0) {
                    countElement.textContent = count > 99 ? '99+' : count;
                    countElement.classList.remove('hidden');
                } else {
                    countElement.classList.add('hidden');
                }
            })
            .catch(error => console.error('Error fetching notification count:', error));
    }

    function fetchLatestNotifications() {
        const listContainer = document.getElementById('notification-list-container');
        const noMessage = document.getElementById('no-notifications-message');
        const loadingMessage = document.getElementById('loading-notifications-message');

        if (!listContainer || !noMessage || !loadingMessage) return; // افزودن چک‌های امنیتی

        // نمایش لودینگ
        listContainer.innerHTML = '';
        loadingMessage.classList.remove('hidden');
        listContainer.appendChild(loadingMessage);
        noMessage.classList.add('hidden');

        fetch(window.AdminRoutes.notificationsLatest)
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(data => {
                loadingMessage.classList.add('hidden');

                if (data.notifications && data.notifications.length > 0) {
                    data.notifications.forEach(notification => {
                        const link = document.createElement('a');
                        link.href = notification.link;
                        link.classList.add('text-gray-700', 'hover:bg-gray-100', 'flex', 'flex-col', 'items-start', 'px-4', 'py-2', 'text-sm', 'border-b', 'border-gray-50');
                        link.setAttribute('role', 'menuitem');
                        link.setAttribute('tabindex', '-1');

                        // افزودن استایل خوانده نشده‌ها
                        if (!notification.read_at) {
                            link.classList.add('bg-blue-50', 'font-semibold');
                        }

                        link.innerHTML = `
                        <span class="truncate w-full">${notification.message}</span>
                        <span class="text-xs ${notification.read_at ? 'text-gray-500' : 'text-blue-500'} mt-0.5">${notification.time_ago}</span>
                    `;

                        // *** افزودن Event Listener برای مارک کردن به عنوان خوانده شده ***
                        if (!notification.read_at) {
                            link.addEventListener('click', (e) => {
                                e.preventDefault(); // جلوگیری از هدایت فوری
                                markNotificationAsRead(notification.id, notification.link);
                            });
                        }

                        listContainer.appendChild(link);
                    });
                } else {
                    noMessage.classList.remove('hidden');
                    listContainer.appendChild(noMessage);
                }
            })
            .catch(error => {
                loadingMessage.classList.add('hidden');
                noMessage.textContent = 'خطا در بارگذاری اعلانات.';
                noMessage.classList.remove('hidden');
                listContainer.appendChild(noMessage);
                console.error('Error fetching latest notifications:', error);
            });
    }

    function markNotificationAsRead(id, redirectLink) {
        const url = window.AdminRoutes.notificationsRead.replace(':id', id);

        fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': window.AdminRoutes.csrfToken,
                'Content-Type': 'application/json'
            }
        })
            .then(response => {
                if (response.ok) {
                    fetchUnreadCount();
                    window.location.href = redirectLink;
                } else {
                    console.error('Failed to mark notification as read');
                    window.location.href = redirectLink;
                }
            })
            .catch(error => {
                console.error('Network or other error:', error);
                window.location.href = redirectLink;
            });
    }

    function toggleSearchModal() {
        const modal = document.getElementById('search-modal');
        if (modal) {
            modal.classList.toggle('hidden');

            if (!modal.classList.contains('hidden')) {
                const searchInput = modal.querySelector('input[name="q"]');
                if (searchInput) {
                    setTimeout(() => searchInput.focus(), 100);
                }
            }
        }
    }

    function closeSearchModalIfClickOutside(event) {
        const modal = document.getElementById('search-modal');
        const content = document.getElementById('search-modal-content');

        if (modal && content && event.target === modal) {
            toggleSearchModal();
        }
    }

    document.addEventListener('DOMContentLoaded', function() {

        fetchUnreadCount();

        setInterval(fetchUnreadCount, 60000);

        const notificationButton = document.getElementById('notification-button');
        const notificationDropdown = document.getElementById('notification-dropdown');

        if (notificationButton && notificationDropdown) {
            notificationButton.addEventListener('click', function(event) {
                const userMenuDropdown = document.getElementById('user-menu-dropdown');
                if (userMenuDropdown && !userMenuDropdown.classList.contains('hidden')) {
                    userMenuDropdown.classList.add('hidden');
                }
                notificationDropdown.classList.toggle('hidden');
                event.stopPropagation();

                if (!notificationDropdown.classList.contains('hidden')) {
                    fetchLatestNotifications();
                }
            });

            document.addEventListener('click', function(event) {
                if (!notificationDropdown.classList.contains('hidden')) {
                    if (!notificationDropdown.contains(event.target) && !notificationButton.contains(event.target)) {
                        notificationDropdown.classList.add('hidden');
                    }
                }
            });

            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape' && !notificationDropdown.classList.contains('hidden')) {
                    notificationDropdown.classList.add('hidden');
                }
            });
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        const userMenuButton = document.getElementById('user-menu-button');
        const userMenuDropdown = document.getElementById('user-menu-dropdown');

        if (userMenuButton && userMenuDropdown) {
            userMenuButton.addEventListener('click', function(event) {
                const notificationDropdown = document.getElementById('notification-dropdown');
                if (notificationDropdown && !notificationDropdown.classList.contains('hidden')) {
                    notificationDropdown.classList.add('hidden');
                }
                userMenuDropdown.classList.toggle('hidden');
                event.stopPropagation();
            });

            document.addEventListener('click', function(event) {
                if (!userMenuDropdown.classList.contains('hidden')) {
                    if (!userMenuDropdown.contains(event.target) && !userMenuButton.contains(event.target)) {
                        userMenuDropdown.classList.add('hidden');
                    }
                }
            });

            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape' && !userMenuDropdown.classList.contains('hidden')) {
                    userMenuDropdown.classList.add('hidden');
                }
            });
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        const deleteButtons = document.querySelectorAll('[data-confirm-delete]');
        deleteButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                const message = button.getAttribute('data-confirm-message') || 'آیا از حذف این آیتم اطمینان دارید؟';

                Swal.fire({
                    title: 'هشدار',
                    text: message,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'بله، حذف شود',
                    cancelButtonText: 'انصراف'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = button.closest('form');
                        if (form) form.submit();
                    }
                });
            });
        });
    });
</script>

@stack('scripts')
</body>
</html>
