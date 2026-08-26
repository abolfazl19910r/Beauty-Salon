<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl" class="rtl">
<head>
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazir-font@v30.1.0/dist/font-face.css" rel="stylesheet" type="text/css" />
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') | پنل مدیریت سالن زیبایی</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
    @stack('styles')
    <style>
        :root {
            --admin-bg: #F8FAFC;
            --admin-surface: #FFFFFF;
            --admin-border: #E2E8F0;
            --admin-text: #1E293B;
            --admin-text-dim: #64748B;
            --admin-text-light: #94A3B8;
            --admin-accent: #334155;
            --admin-accent-hover: #1E293B;
            --admin-accent-light: #F1F5F9;
        }

        .persian-number {
            -moz-font-feature-settings: "ss02";
            -webkit-font-feature-settings: "ss02";
            font-feature-settings: "ss02";
        }

        .sidebar-active {
            background: linear-gradient(90deg, rgba(51, 65, 85, 0.08) 0%, rgba(51, 65, 85, 0.18) 100%);
            border-right: 3px solid var(--admin-accent);
            color: var(--admin-accent) !important;
        }

        .sidebar-active svg {
            opacity: 1 !important;
        }

        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(241, 245, 249, 0.8);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: rgba(148, 163, 184, 0.5);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: rgba(100, 116, 139, 0.7);
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
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -2px rgba(0, 0, 0, 0.04);
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
            background: var(--admin-accent-light);
            border-right: 3px solid var(--admin-accent);
        }

        /* Main admin buttons */
        .admin-btn-primary {
            background-color: var(--admin-accent);
            color: #fff;
            transition: background-color 0.2s;
        }
        .admin-btn-primary:hover {
            background-color: var(--admin-accent-hover);
        }

        /* Admin card */
        .admin-card {
            background: var(--admin-surface);
            border: 1px solid var(--admin-border);
            border-radius: 0.75rem;
        }
    </style>
</head>
<body class="rtl font-vazir" style="background-color: var(--admin-bg); color: var(--admin-text);">
<div class="flex h-screen overflow-hidden">
    <!-- Sidebar -->
    <aside class="w-64 min-h-screen shadow-sm border-l overflow-y-auto hidden md:block"
           style="background: var(--admin-surface); border-color: var(--admin-border);">
        <div class="p-4 border-b" style="border-color: var(--admin-border);">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-bold flex items-center" style="color: var(--admin-text);">
                    <svg class="w-8 h-8 ml-2" style="color: var(--admin-accent);" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="8.5" cy="7" r="4"></circle>
                        <path d="M20 8v6M23 11h-6"></path>
                    </svg>
                    <span style="color: var(--admin-accent);">پنل مدیریت</span>
                </h2>
            </div>
            <p class="text-xs mt-2" style="color: var(--admin-text-light);">سیستم مدیریت سالن زیبایی</p>
        </div>

        <nav class="mt-4 px-2">
            <div class="py-2">
                <h3 class="text-xs font-semibold px-3 mb-2 uppercase tracking-wider" style="color: var(--admin-text-light);">داشبورد</h3>
                <a href="{{ route('admin.dashboard') }}"
                   class="flex items-center px-3 py-2.5 mb-1 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.dashboard') ? 'sidebar-active' : '' }}"
                   style="{{ request()->routeIs('admin.dashboard') ? '' : 'color: var(--admin-text-dim);' }}"
                   onmouseover="{{ request()->routeIs('admin.dashboard') ? '' : 'this.style.backgroundColor=\"var(--admin-accent-light)\"' }}"
                   onmouseout="{{ request()->routeIs('admin.dashboard') ? '' : 'this.style.backgroundColor=\"\"' }}">
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
                   class="flex items-center px-3 py-2.5 mb-1 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.reports*') ? 'sidebar-active' : '' }}"
                   style="{{ request()->routeIs('admin.reports*') ? '' : 'color: var(--admin-text-dim);' }}"
                   onmouseover="{{ request()->routeIs('admin.reports*') ? '' : 'this.style.backgroundColor=\"var(--admin-accent-light)\"' }}"
                   onmouseout="{{ request()->routeIs('admin.reports*') ? '' : 'this.style.backgroundColor=\"\"' }}">
                    <svg class="w-5 h-5 ml-2 opacity-75" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21.21 15.89A10 10 0 1 1 8 2.83"></path>
                        <path d="M22 12A10 10 0 0 0 12 2v10z"></path>
                    </svg>
                    گزارشات
                </a>
                @endpermission
            </div>

            <div class="py-2">
                <h3 class="text-xs font-semibold px-3 mb-2 uppercase tracking-wider" style="color: var(--admin-text-light);">مدیریت</h3>
                @permission('view-services')
                <a href="{{ route('admin.services.index') }}"
                   class="flex items-center px-3 py-2.5 mb-1 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.services*') ? 'sidebar-active' : '' }}"
                   style="{{ request()->routeIs('admin.services*') ? '' : 'color: var(--admin-text-dim);' }}"
                   onmouseover="{{ request()->routeIs('admin.services*') ? '' : 'this.style.backgroundColor=\"var(--admin-accent-light)\"' }}"
                   onmouseout="{{ request()->routeIs('admin.services*') ? '' : 'this.style.backgroundColor=\"\"' }}">
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
                   class="flex items-center px-3 py-2.5 mb-1 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.specialists*') ? 'sidebar-active' : '' }}"
                   style="{{ request()->routeIs('admin.specialists*') ? '' : 'color: var(--admin-text-dim);' }}"
                   onmouseover="{{ request()->routeIs('admin.specialists*') ? '' : 'this.style.backgroundColor=\"var(--admin-accent-light)\"' }}"
                   onmouseout="{{ request()->routeIs('admin.specialists*') ? '' : 'this.style.backgroundColor=\"\"' }}">
                    <svg class="w-5 h-5 ml-2 opacity-75" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    متخصصین
                </a>
                @endpermission

                @permission('view-specialists')
                <a href="{{ route('admin.leaves.index') }}"
                   class="flex items-center px-3 py-2.5 mb-1 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.leaves*') ? 'sidebar-active' : '' }}"
                   style="{{ request()->routeIs('admin.leaves*') ? '' : 'color: var(--admin-text-dim);' }}"
                   onmouseover="{{ request()->routeIs('admin.leaves*') ? '' : 'this.style.backgroundColor=\"var(--admin-accent-light)\"' }}"
                   onmouseout="{{ request()->routeIs('admin.leaves*') ? '' : 'this.style.backgroundColor=\"\"' }}">
                    <svg class="w-5 h-5 ml-2 opacity-75" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    مرخصی‌ها
                </a>
                @endpermission

                @permission('view-bookings')
                <a href="{{ route('admin.bookings.index') }}"
                   class="flex items-center px-3 py-2.5 mb-1 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.bookings*') ? 'sidebar-active' : '' }}"
                   style="{{ request()->routeIs('admin.bookings*') ? '' : 'color: var(--admin-text-dim);' }}"
                   onmouseover="{{ request()->routeIs('admin.bookings*') ? '' : 'this.style.backgroundColor=\"var(--admin-accent-light)\"' }}"
                   onmouseout="{{ request()->routeIs('admin.bookings*') ? '' : 'this.style.backgroundColor=\"\"' }}">
                    <svg class="w-5 h-5 ml-2 opacity-75" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    نوبت‌ها
                </a>
                @endpermission

                @permission('view-reviews')
                <a href="{{ route('admin.reviews.index') }}"
                   class="flex items-center px-3 py-2.5 mb-1 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.reviews*') ? 'sidebar-active' : '' }}"
                   style="{{ request()->routeIs('admin.reviews*') ? '' : 'color: var(--admin-text-dim);' }}"
                   onmouseover="{{ request()->routeIs('admin.reviews*') ? '' : 'this.style.backgroundColor=\"var(--admin-accent-light)\"' }}"
                   onmouseout="{{ request()->routeIs('admin.reviews*') ? '' : 'this.style.backgroundColor=\"\"' }}">
                    <svg class="w-5 h-5 ml-2 opacity-75" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                    نظرات و ارزیابی‌ها
                </a>
                @endpermission

                @permission('view-categories')
                <a href="{{ route('admin.categories.index') }}"
                   class="flex items-center px-3 py-2.5 mb-1 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.categories*') ? 'sidebar-active' : '' }}"
                   style="{{ request()->routeIs('admin.categories*') ? '' : 'color: var(--admin-text-dim);' }}"
                   onmouseover="{{ request()->routeIs('admin.categories*') ? '' : 'this.style.backgroundColor=\"var(--admin-accent-light)\"' }}"
                   onmouseout="{{ request()->routeIs('admin.categories*') ? '' : 'this.style.backgroundColor=\"\"' }}">
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
                <h3 class="text-xs font-semibold px-3 mb-2 uppercase tracking-wider" style="color: var(--admin-text-light);">سیستم‌ها</h3>

                <a href="{{ route('admin.notifications.index') }}"
                   class="flex items-center px-3 py-2.5 mb-1 text-sm font-medium rounded-lg transition-colors justify-between {{ request()->routeIs('admin.notifications*') ? 'sidebar-active' : '' }}"
                   style="{{ request()->routeIs('admin.notifications*') ? '' : 'color: var(--admin-text-dim);' }}"
                   onmouseover="{{ request()->routeIs('admin.notifications*') ? '' : 'this.style.backgroundColor=\"var(--admin-accent-light)\"' }}"
                   onmouseout="{{ request()->routeIs('admin.notifications*') ? '' : 'this.style.backgroundColor=\"\"' }}">
                    <span class="flex items-center">
                        <svg class="w-5 h-5 ml-2 opacity-75" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                        </svg>
                        اعلانات
                    </span>
                    <span id="sidebar-notification-count" class="mr-2 px-2.5 py-0.5 rounded-full text-xs font-medium hidden"
                          style="background: #FEE2E2; color: #991B1B;">
                    </span>
                </a>

                @permission('view-loyalty')
                <a href="{{ route('admin.loyalty.index') }}"
                   class="flex items-center px-3 py-2.5 mb-1 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.loyalty*') ? 'sidebar-active' : '' }}"
                   style="{{ request()->routeIs('admin.loyalty*') ? '' : 'color: var(--admin-text-dim);' }}"
                   onmouseover="{{ request()->routeIs('admin.loyalty*') ? '' : 'this.style.backgroundColor=\"var(--admin-accent-light)\"' }}"
                   onmouseout="{{ request()->routeIs('admin.loyalty*') ? '' : 'this.style.backgroundColor=\"\"' }}">
                    <svg class="w-5 h-5 ml-2 opacity-75" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                    </svg>
                    امتیازات
                </a>
                @endpermission

                @permission('view-blog')
                <a href="{{ route('admin.blog.index') }}"
                   class="flex items-center px-3 py-2.5 mb-1 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.blog*') ? 'sidebar-active' : '' }}"
                   style="{{ request()->routeIs('admin.blog*') ? '' : 'color: var(--admin-text-dim);' }}"
                   onmouseover="{{ request()->routeIs('admin.blog*') ? '' : 'this.style.backgroundColor=\"var(--admin-accent-light)\"' }}"
                   onmouseout="{{ request()->routeIs('admin.blog*') ? '' : 'this.style.backgroundColor=\"\"' }}">
                    <svg class="w-5 h-5 ml-2 opacity-75" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                    </svg>
                    وبلاگ
                </a>
                @endpermission

                @permission('view-gallery')
                <a href="{{ route('admin.gallery.index') }}"
                   class="flex items-center px-3 py-2.5 mb-1 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.gallery*') ? 'sidebar-active' : '' }}"
                   style="{{ request()->routeIs('admin.gallery*') ? '' : 'color: var(--admin-text-dim);' }}"
                   onmouseover="{{ request()->routeIs('admin.gallery*') ? '' : 'this.style.backgroundColor=\"var(--admin-accent-light)\"' }}"
                   onmouseout="{{ request()->routeIs('admin.gallery*') ? '' : 'this.style.backgroundColor=\"\"' }}">
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
                   class="flex items-center px-3 py-2.5 mb-1 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.announcements*') ? 'sidebar-active' : '' }}"
                   style="{{ request()->routeIs('admin.announcements*') ? '' : 'color: var(--admin-text-dim);' }}"
                   onmouseover="{{ request()->routeIs('admin.announcements*') ? '' : 'this.style.backgroundColor=\"var(--admin-accent-light)\"' }}"
                   onmouseout="{{ request()->routeIs('admin.announcements*') ? '' : 'this.style.backgroundColor=\"\"' }}">
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

                @permission('view-discount-codes')
                <a href="{{ route('admin.discount-codes.index') }}"
                   class="flex items-center px-3 py-2.5 mb-1 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.discount-codes*') ? 'sidebar-active' : '' }}"
                   style="{{ request()->routeIs('admin.discount-codes*') ? '' : 'color: var(--admin-text-dim);' }}"
                   onmouseover="{{ request()->routeIs('admin.discount-codes*') ? '' : 'this.style.backgroundColor=\"var(--admin-accent-light)\"' }}"
                   onmouseout="{{ request()->routeIs('admin.discount-codes*') ? '' : 'this.style.backgroundColor=\"\"' }}">
                    <svg class="w-5 h-5 ml-2 opacity-75" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path>
                        <line x1="7" y1="7" x2="7.01" y2="7"></line>
                    </svg>
                    کدهای تخفیف
                </a>
                @endpermission

                @permission('view-security-logs')
                <a href="{{ route('admin.security.logs') }}"
                   class="flex items-center px-3 py-2.5 mb-1 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.security*') ? 'sidebar-active' : '' }}"
                   style="{{ request()->routeIs('admin.security*') ? '' : 'color: var(--admin-text-dim);' }}"
                   onmouseover="{{ request()->routeIs('admin.security*') ? '' : 'this.style.backgroundColor=\"var(--admin-accent-light)\"' }}"
                   onmouseout="{{ request()->routeIs('admin.security*') ? '' : 'this.style.backgroundColor=\"\"' }}">
                    <svg class="w-5 h-5 ml-2 opacity-75" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                    </svg>
                    امنیت
                </a>
                @endpermission

                @permission('view-security-logs')
                <a href="{{ route('admin.notification-settings.index') }}"
                   class="flex items-center px-3 py-2.5 mb-1 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.notification-settings*') ? 'sidebar-active' : '' }}"
                   style="{{ request()->routeIs('admin.notification-settings*') ? '' : 'color: var(--admin-text-dim);' }}"
                   onmouseover="{{ request()->routeIs('admin.notification-settings*') ? '' : 'this.style.backgroundColor=\"var(--admin-accent-light)\"' }}"
                   onmouseout="{{ request()->routeIs('admin.notification-settings*') ? '' : 'this.style.backgroundColor=\"\"' }}">
                    <svg class="w-5 h-5 ml-2 opacity-75" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                    </svg>
                    تنظیمات اطلاع‌رسانی
                </a>
                @endpermission
            </div>

            <div class="py-2">
                <h3 class="text-xs font-semibold px-3 mb-2 uppercase tracking-wider" style="color: var(--admin-text-light);">مدیریت کاربران</h3>
                @permission('view-users')
                <a href="{{ route('admin.users.index') }}"
                   class="flex items-center px-3 py-2.5 mb-1 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.users*') ? 'sidebar-active' : '' }}"
                   style="{{ request()->routeIs('admin.users*') ? '' : 'color: var(--admin-text-dim);' }}"
                   onmouseover="{{ request()->routeIs('admin.users*') ? '' : 'this.style.backgroundColor=\"var(--admin-accent-light)\"' }}"
                   onmouseout="{{ request()->routeIs('admin.users*') ? '' : 'this.style.backgroundColor=\"\"' }}">
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
                   class="flex items-center px-3 py-2.5 mb-1 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.permissions*') ? 'sidebar-active' : '' }}"
                   style="{{ request()->routeIs('admin.permissions*') ? '' : 'color: var(--admin-text-dim);' }}"
                   onmouseover="{{ request()->routeIs('admin.permissions*') ? '' : 'this.style.backgroundColor=\"var(--admin-accent-light)\"' }}"
                   onmouseout="{{ request()->routeIs('admin.permissions*') ? '' : 'this.style.backgroundColor=\"\"' }}">
                    <svg class="w-5 h-5 ml-2 opacity-75" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    دسترسی‌ها
                </a>
                @endpermission

                @permission('manage-roles')
                <a href="{{ route('admin.roles.index') }}"
                   class="flex items-center px-3 py-2.5 mb-1 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.roles*') ? 'sidebar-active' : '' }}"
                   style="{{ request()->routeIs('admin.roles*') ? '' : 'color: var(--admin-text-dim);' }}"
                   onmouseover="{{ request()->routeIs('admin.roles*') ? '' : 'this.style.backgroundColor=\"var(--admin-accent-light)\"' }}"
                   onmouseout="{{ request()->routeIs('admin.roles*') ? '' : 'this.style.backgroundColor=\"\"' }}">
                    <svg class="w-5 h-5 ml-2 opacity-75" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 11h6m-3-3v6M17 3a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h10z"></path>
                    </svg>
                    نقش‌ها
                </a>
                @endpermission
            </div>

            <div class="py-2">
                <h3 class="text-xs font-semibold px-3 mb-2 uppercase tracking-wider" style="color: var(--admin-text-light);">امور مالی</h3>

                <a href="{{ route('admin.wallet.index') }}"
                   class="flex items-center px-3 py-2.5 mb-1 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.wallet.index') ? 'sidebar-active' : '' }}"
                   style="{{ request()->routeIs('admin.wallet.index') ? '' : 'color: var(--admin-text-dim);' }}"
                   onmouseover="{{ request()->routeIs('admin.wallet.index') ? '' : 'this.style.backgroundColor=\"var(--admin-accent-light)\"' }}"
                   onmouseout="{{ request()->routeIs('admin.wallet.index') ? '' : 'this.style.backgroundColor=\"\"' }}">
                    <svg class="w-5 h-5 ml-2 opacity-75" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg>
                    کیف پول‌ها
                </a>

                <a href="{{ route('admin.wallet.withdrawals') }}"
                   class="flex items-center px-3 py-2.5 mb-1 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.wallet.withdrawals*') ? 'sidebar-active' : '' }}"
                   style="{{ request()->routeIs('admin.wallet.withdrawals*') ? '' : 'color: var(--admin-text-dim);' }}"
                   onmouseover="{{ request()->routeIs('admin.wallet.withdrawals*') ? '' : 'this.style.backgroundColor=\"var(--admin-accent-light)\"' }}"
                   onmouseout="{{ request()->routeIs('admin.wallet.withdrawals*') ? '' : 'this.style.backgroundColor=\"\"' }}">
                    <svg class="w-5 h-5 ml-2 opacity-75" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    درخواست‌های برداشت
                </a>

                <a href="{{ route('admin.wallet.settings') }}"
                   class="flex items-center px-3 py-2.5 mb-1 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.wallet.settings') ? 'sidebar-active' : '' }}"
                   style="{{ request()->routeIs('admin.wallet.settings') ? '' : 'color: var(--admin-text-dim);' }}"
                   onmouseover="{{ request()->routeIs('admin.wallet.settings') ? '' : 'this.style.backgroundColor=\"var(--admin-accent-light)\"' }}"
                   onmouseout="{{ request()->routeIs('admin.wallet.settings') ? '' : 'this.style.backgroundColor=\"\"' }}">
                    <svg class="w-5 h-5 ml-2 opacity-75" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    تنظیمات مالی
                </a>
            </div>

            <div class="py-2">
                <h3 class="text-xs font-semibold px-3 mb-2 uppercase tracking-wider" style="color: var(--admin-text-light);">تنظیمات</h3>
                <a href="{{ route('admin.profile.edit') }}"
                   class="flex items-center px-3 py-2.5 mb-1 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.profile*') ? 'sidebar-active' : '' }}"
                   style="{{ request()->routeIs('admin.profile*') ? '' : 'color: var(--admin-text-dim);' }}"
                   onmouseover="{{ request()->routeIs('admin.profile*') ? '' : 'this.style.backgroundColor=\"var(--admin-accent-light)\"' }}"
                   onmouseout="{{ request()->routeIs('admin.profile*') ? '' : 'this.style.backgroundColor=\"\"' }}">
                    <svg class="w-5 h-5 ml-2 opacity-75" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    پروفایل
                </a>

                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <button type="submit"
                            class="w-full flex items-center px-3 py-2.5 mb-1 text-sm font-medium rounded-lg transition-colors text-right"
                            style="color: var(--admin-text-dim);"
                            onmouseover="this.style.backgroundColor='#FEF2F2'; this.style.color='#DC2626';"
                            onmouseout="this.style.backgroundColor=''; this.style.color='var(--admin-text-dim)';">
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

    <div class="flex-1 flex flex-col relative overflow-hidden">
        <!-- Header -->
        <header class="sticky top-0 z-30 shadow-sm" style="background: var(--admin-surface); border-bottom: 1px solid var(--admin-border);">
            <div class="px-4 py-3 flex justify-between items-center">
                <button type="button" class="md:hidden focus:outline-none" style="color: var(--admin-text-dim);" onclick="toggleSidebar()">
                    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="3" y1="12" x2="21" y2="12"></line>
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <line x1="3" y1="18" x2="21" y2="18"></line>
                    </svg>
                </button>

                <h1 class="text-lg font-bold md:hidden" style="color: var(--admin-text);">@yield('title')</h1>

                <div class="flex items-center space-x-3 space-x-reverse">
                    <!-- Search button -->
                    <button type="button"
                            id="search-button"
                            class="p-2 rounded-lg focus:outline-none transition-colors"
                            style="color: var(--admin-text-dim);"
                            onmouseover="this.style.backgroundColor='var(--admin-accent-light)'"
                            onmouseout="this.style.backgroundColor=''"
                            onclick="toggleSearchModal()">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                    </button>

                    <!-- Announcements dropdown -->
                    <div class="relative inline-block text-left">
                        <button type="button"
                                id="notification-button"
                                class="p-2 rounded-lg focus:outline-none transition-colors relative"
                                style="color: var(--admin-text-dim);"
                                onmouseover="this.style.backgroundColor='var(--admin-accent-light)'"
                                onmouseout="this.style.backgroundColor=''"
                                aria-expanded="false" aria-haspopup="true">
                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                                <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                            </svg>
                            <span id="notification-count"
                                  class="absolute top-0 right-0 hidden items-center justify-center px-1.5 py-0.5 text-xs font-bold leading-none rounded-full"
                                  style="background: #EF4444; color: #fff; min-width: 18px; text-align: center;">0</span>
                        </button>

                        <div id="notification-dropdown"
                             class="hidden absolute right-0 z-50 mt-2 w-80 rounded-xl shadow-lg ring-1 focus:outline-none"
                             style="background: var(--admin-surface); border-color: var(--admin-border); border: 1px solid var(--admin-border);"
                             role="menu" aria-orientation="vertical" aria-labelledby="notification-button" tabindex="-1">
                            <div class="px-4 py-3 border-b" style="border-color: var(--admin-border);">
                                <p class="text-sm font-bold" style="color: var(--admin-text);">اعلانات جدید</p>
                            </div>
                            <div id="notification-list-container" class="py-1 max-h-72 overflow-y-auto" role="none">
                                <p id="loading-notifications-message" class="px-4 py-3 text-sm" style="color: var(--admin-text-dim);">در حال بارگذاری...</p>
                                <p id="no-notifications-message" class="px-4 py-3 text-sm hidden" style="color: var(--admin-text-dim);">اعلانی برای نمایش وجود ندارد.</p>
                            </div>
                            <div class="border-t" style="border-color: var(--admin-border);">
                                <a href="{{ route('admin.notifications.index') }}"
                                   class="w-full text-center block px-4 py-2.5 text-sm font-medium transition-colors"
                                   style="color: var(--admin-accent);"
                                   role="menuitem" tabindex="-1">
                                    مشاهده همه اعلانات
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- User menu -->
                    <div class="relative inline-block text-left">
                        <button type="button"
                                class="inline-flex items-center justify-center w-9 h-9 rounded-full font-medium text-sm focus:outline-none transition-colors"
                                style="background: var(--admin-accent-light); color: var(--admin-accent);"
                                id="user-menu-button" aria-expanded="false" aria-haspopup="true">
                            {{ mb_substr(optional(auth()->user())->name ?? 'کاربر', 0, 1) }}
                        </button>

                        <div id="user-menu-dropdown"
                             class="hidden absolute right-0 z-50 mt-2 w-56 rounded-xl shadow-lg focus:outline-none"
                             style="background: var(--admin-surface); border: 1px solid var(--admin-border);"
                             role="menu" aria-orientation="vertical" aria-labelledby="user-menu-button" tabindex="-1">
                            <div class="py-1" role="none">
                                <div class="px-4 py-3 border-b" style="border-color: var(--admin-border);">
                                    <p class="text-sm font-medium truncate" style="color: var(--admin-text);">{{ auth()->user()->name ?? 'کاربر' }}</p>
                                    <p class="text-xs truncate mt-0.5" style="color: var(--admin-text-dim);">{{ auth()->user()->email ?? '' }}</p>
                                </div>

                                <a href="{{ route('admin.profile.show') }}"
                                   class="flex items-center px-4 py-2.5 text-sm transition-colors"
                                   style="color: var(--admin-text-dim);"
                                   onmouseover="this.style.backgroundColor='var(--admin-accent-light)'; this.style.color='var(--admin-text)';"
                                   onmouseout="this.style.backgroundColor=''; this.style.color='var(--admin-text-dim)';"
                                   role="menuitem" tabindex="-1">
                                    <svg class="w-4 h-4 ml-2 opacity-60" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-6-3a2 2 0 11-4 0 2 2 0 014 0zM8 12a6 6 0 00-5.43 3.59A8 8 0 0118 10a8 8 0 01-4.57 7.59A6 6 0 008 12z" clip-rule="evenodd" />
                                    </svg>
                                    نمایش پروفایل
                                </a>
                                <a href="{{ route('admin.profile.edit') }}"
                                   class="flex items-center px-4 py-2.5 text-sm transition-colors"
                                   style="color: var(--admin-text-dim);"
                                   onmouseover="this.style.backgroundColor='var(--admin-accent-light)'; this.style.color='var(--admin-text)';"
                                   onmouseout="this.style.backgroundColor=''; this.style.color='var(--admin-text-dim)';"
                                   role="menuitem" tabindex="-1">
                                    <svg class="w-4 h-4 ml-2 opacity-60" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z" />
                                        <path fill-rule="evenodd" d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" clip-rule="evenodd" />
                                    </svg>
                                    ویرایش پروفایل
                                </a>

                                <form method="POST" action="{{ route('logout') }}" role="none" class="border-t" style="border-color: var(--admin-border);">
                                    @csrf
                                    <button type="submit"
                                            class="w-full text-right flex items-center px-4 py-2.5 text-sm transition-colors"
                                            style="color: #DC2626;"
                                            onmouseover="this.style.backgroundColor='#FEF2F2';"
                                            onmouseout="this.style.backgroundColor='';"
                                            role="menuitem" tabindex="-1">
                                        <svg class="w-4 h-4 ml-2 opacity-70" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
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

            <!-- Breadcrumb -->
            <div class="hidden md:flex px-4 py-2 border-t text-sm" style="background: var(--admin-accent-light); border-color: var(--admin-border);">
                <a href="{{ route('admin.dashboard') }}" class="transition-colors" style="color: var(--admin-accent);"
                   onmouseover="this.style.color='var(--admin-accent-hover)'"
                   onmouseout="this.style.color='var(--admin-accent)'">پنل مدیریت</a>
                <span class="mx-2" style="color: var(--admin-text-light);">/</span>
                <span style="color: var(--admin-text-dim);">@yield('title')</span>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto p-4" style="background: var(--admin-bg);">
            @if(session('success'))
                <div id="flash-success" class="mb-4 border-r-4 border-green-500 p-4 fade-in rounded-lg shadow-sm flex items-center justify-between"
                     style="background: #F0FDF4; color: #166534;" role="alert">
                    <div class="flex items-center">
                        <svg class="h-5 w-5 text-green-500 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        <span class="mr-3">{{ session('success') }}</span>
                    </div>
                    <button onclick="document.getElementById('flash-success').remove()" class="mr-2 opacity-60 hover:opacity-100 transition-opacity">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <script>
                    setTimeout(function() {
                        const el = document.getElementById('flash-success');
                        if (el) { el.style.transition = 'opacity 0.5s'; el.style.opacity = '0'; setTimeout(() => el.remove(), 500); }
                    }, 4000);
                </script>
            @endif

            @if(session('error'))
                <div id="flash-error" class="mb-4 border-r-4 border-red-500 p-4 fade-in rounded-lg shadow-sm flex items-center justify-between"
                     style="background: #FEF2F2; color: #991B1B;" role="alert">
                    <div class="flex items-center">
                        <svg class="h-5 w-5 text-red-500 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                        <span class="mr-3">{{ session('error') }}</span>
                    </div>
                    <button onclick="document.getElementById('flash-error').remove()" class="mr-2 opacity-60 hover:opacity-100 transition-opacity">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <script>
                    setTimeout(function() {
                        const el = document.getElementById('flash-error');
                        if (el) { el.style.transition = 'opacity 0.5s'; el.style.opacity = '0'; setTimeout(() => el.remove(), 500); }
                    }, 5000);
                </script>
            @endif

            <div class="fade-in">
                @yield('content')
            </div>
        </main>

        <footer class="py-3 px-4 text-center text-xs border-t"
                style="background: var(--admin-surface); border-color: var(--admin-border); color: var(--admin-text-light);">
            <p>© {{ date('Y') }} سیستم مدیریت سالن زیبایی. تمامی حقوق محفوظ است.</p>
        </footer>
    </div>
</div>

<!-- Search modal -->
<div id="search-modal"
     class="fixed inset-0 z-[100] hidden flex items-start justify-center pt-16"
     style="background: rgba(15, 23, 42, 0.5);"
     aria-modal="true" role="dialog"
     onclick="closeSearchModalIfClickOutside(event)">
    <div class="rounded-xl shadow-xl w-full max-w-lg mx-4 overflow-hidden fade-in"
         style="background: var(--admin-surface);"
         role="document"
         id="search-modal-content">
        <div class="p-5">
            <h3 class="text-base font-semibold border-b pb-3 mb-4" style="color: var(--admin-text); border-color: var(--admin-border);">جستجوی سریع در پنل</h3>
            <form action="{{ route('admin.search.index') }}" method="GET">
                <div class="relative">
                    <input type="search" name="q" placeholder="جستجو بین خدمات، متخصصین، کاربران..."
                           class="w-full py-2.5 px-4 pr-10 text-sm rounded-lg outline-none transition"
                           style="border: 1px solid var(--admin-border); background: var(--admin-bg); color: var(--admin-text);"
                           onfocus="this.style.borderColor='var(--admin-accent)'"
                           onblur="this.style.borderColor='var(--admin-border)'"
                           autofocus>
                    <div class="absolute right-3 top-0 bottom-0 flex items-center pointer-events-none" style="color: var(--admin-text-light);">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                    </div>
                </div>
                <div class="mt-4 flex justify-end gap-2">
                    <button type="button" onclick="toggleSearchModal()"
                            class="px-4 py-2 text-sm font-medium rounded-lg transition-colors"
                            style="background: var(--admin-accent-light); color: var(--admin-text-dim);"
                            onmouseover="this.style.background='var(--admin-border)'"
                            onmouseout="this.style.background='var(--admin-accent-light)'">
                        انصراف
                    </button>
                    <button type="submit"
                            class="admin-btn-primary px-4 py-2 text-sm font-medium rounded-lg">
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

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    window.AdminRoutes = {
        notificationsIndex: '{{ route("admin.notifications.index") }}',
        notificationsLatest: '{{ route("admin.notifications.latest") }}',
        notificationsCount: '{{ route("admin.notifications.count") }}',
        notificationsRead: '{{ route("admin.notifications.read", "000") }}'.replace('000', ':id'),
        csrfToken: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
    };

    function fetchUnreadCount() {
        fetch(window.AdminRoutes.notificationsCount)
            .then(response => response.json())
            .then(data => {
                const countElement = document.getElementById('notification-count');
                const sidebarCount = document.getElementById('sidebar-notification-count');
                const count = data.count;

                if (count > 0) {
                    const label = count > 99 ? '99+' : count;
                    countElement.textContent = label;
                    countElement.classList.remove('hidden');
                    countElement.classList.add('flex');
                    if (sidebarCount) {
                        sidebarCount.textContent = label;
                        sidebarCount.classList.remove('hidden');
                    }
                } else {
                    countElement.classList.add('hidden');
                    countElement.classList.remove('flex');
                    if (sidebarCount) sidebarCount.classList.add('hidden');
                }
            })
            .catch(error => console.error('Error fetching notification count:', error));
    }

    // ⭐ The admin/notifications/index.blade.php and show.blade.php pages already called
    // window.refreshNotificationCount() after each operation (read/toggle) to update the header counter badge without requiring a full page refresh — but this
    // function was not defined anywhere in the project, so the badge update only happened on a full page refresh (or the 60-second timer
    // below). It is now connected to fetchUnreadCount with an alias.
    window.refreshNotificationCount = fetchUnreadCount;

    // ⭐ The same pages also used a localStorage.setItem('notification_updated', ...)
    // (presumably to coordinate between multiple open browser tabs) but no listener was defined for this
    // key — this section was also completed so that if the admin has multiple open tabs,
    // marking a tab will update the badge in other tabs as well.
    window.addEventListener('storage', function (e) {
        if (e.key === 'notification_updated') fetchUnreadCount();
    });

    function fetchLatestNotifications() {
        const listContainer = document.getElementById('notification-list-container');
        const noMessage = document.getElementById('no-notifications-message');
        const loadingMessage = document.getElementById('loading-notifications-message');

        if (!listContainer || !noMessage || !loadingMessage) return;

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
                        link.classList.add('flex', 'flex-col', 'items-start', 'px-4', 'py-2.5', 'text-sm', 'border-b', 'transition-colors');
                        link.style.borderColor = 'var(--admin-border)';
                        link.style.color = 'var(--admin-text-dim)';
                        link.setAttribute('role', 'menuitem');
                        link.setAttribute('tabindex', '-1');

                        if (!notification.read_at) {
                            link.classList.add('notification-item-unread');
                            link.style.fontWeight = '600';
                        }

                        link.addEventListener('mouseover', () => {
                            if (!notification.read_at) return;
                            link.style.backgroundColor = 'var(--admin-accent-light)';
                        });
                        link.addEventListener('mouseout', () => {
                            if (!notification.read_at) return;
                            link.style.backgroundColor = '';
                        });

                        link.innerHTML = `
                            <span class="truncate w-full" style="color: var(--admin-text);">${notification.message}</span>
                            <span class="text-xs mt-0.5" style="color: ${notification.read_at ? 'var(--admin-text-light)' : 'var(--admin-accent)'};">${notification.time_ago}</span>
                        `;

                        if (!notification.read_at) {
                            link.addEventListener('click', (e) => {
                                e.preventDefault();
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
                    window.location.href = redirectLink;
                }
            })
            .catch(() => {
                window.location.href = redirectLink;
            });
    }

    function toggleSidebar() {
        const sidebar = document.querySelector('aside');
        const backdrop = document.getElementById('sidebar-backdrop');
        if (sidebar) sidebar.classList.toggle('hidden');
        if (backdrop) backdrop.classList.toggle('hidden');
    }

    function toggleSearchModal() {
        const modal = document.getElementById('search-modal');
        if (modal) {
            modal.classList.toggle('hidden');
            if (!modal.classList.contains('hidden')) {
                const searchInput = modal.querySelector('input[name="q"]');
                if (searchInput) setTimeout(() => searchInput.focus(), 100);
            }
        }
    }

    function closeSearchModalIfClickOutside(event) {
        const modal = document.getElementById('search-modal');
        const content = document.getElementById('search-modal-content');
        if (modal && content && event.target === modal) toggleSearchModal();
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

        // SweetAlert2 — Confirm deletion
        const deleteButtons = document.querySelectorAll('[data-confirm-delete]');
        deleteButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                if (button.disabled) return;
                const message = button.getAttribute('data-confirm-message') || 'آیا از حذف این آیتم اطمینان دارید؟';

                Swal.fire({
                    title: 'هشدار',
                    text: message,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#334155',
                    cancelButtonColor: '#94A3B8',
                    confirmButtonText: 'بله، حذف شود',
                    cancelButtonText: 'انصراف'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = button.closest('form');
                        if (form && !form.dataset.submitted) {
                            form.dataset.submitted = '1';
                            button.disabled = true;
                            form.submit();
                        }
                    }
                });
            });
        });

        // SweetAlert2 — Confirm sensitive but non-destructive actions (e.g. manual settlement)
        const confirmActionButtons = document.querySelectorAll('[data-confirm-action]');
        confirmActionButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                if (button.disabled) return;
                const message = button.getAttribute('data-confirm-message') || 'آیا از انجام این عملیات اطمینان دارید؟';
                const confirmText = button.getAttribute('data-confirm-text') || 'بله، انجام شود';

                Swal.fire({
                    title: 'تایید عملیات',
                    text: message,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#16A34A',
                    cancelButtonColor: '#94A3B8',
                    confirmButtonText: confirmText,
                    cancelButtonText: 'انصراف'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = button.closest('form');
                        if (form && !form.dataset.submitted) {
                            form.dataset.submitted = '1';
                            button.disabled = true;
                            form.submit();
                        }
                    }
                });
            });
        });

        // Close the search modal with Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const modal = document.getElementById('search-modal');
                if (modal && !modal.classList.contains('hidden')) toggleSearchModal();
            }
        });
    });
</script>

@stack('scripts')
</body>
</html>
