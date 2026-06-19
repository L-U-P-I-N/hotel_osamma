<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'الفندق السعودي') - نظام إدارة الفندق</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { cairo: ['Cairo', 'sans-serif'] },
                    colors: {
                        primary: { DEFAULT: '#0F4C75', 50:'#e8f0f7', 100:'#c5d8ea', 200:'#9fbedd', 300:'#79a4cf', 400:'#5b90c5', 500:'#3d7cbb', 600:'#2d6aab', 700:'#1e578f', 800:'#0F4C75', 900:'#0a3555' },
                        accent:  { DEFAULT: '#D4A574', 50:'#fdf6ee', 100:'#f9e8d2', 200:'#f3d5b0', 300:'#ecc18e', 400:'#e6ae6c', 500:'#D4A574', 600:'#c08d5a', 700:'#a67040', 800:'#8c5530', 900:'#6e3f22' },
                    }
                }
            }
        }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: 'Cairo', sans-serif; }
        [x-cloak] { display: none !important; }

        /* Sidebar links */
        .nav-link {
            display: flex; align-items: center; gap: 10px;
            padding: 9px 14px; border-radius: 10px;
            font-size: 0.8125rem; font-weight: 500;
            color: #93b8d4; transition: all 0.18s;
            white-space: nowrap; overflow: hidden;
        }
        .nav-link:hover { background: rgba(255,255,255,0.08); color: #fff; }
        .nav-link.active { background: rgba(255,255,255,0.13); color: #fff; font-weight: 600; }
        .nav-link.active .nav-dot { opacity: 1; }

        .nav-section-label {
            font-size: 0.65rem; font-weight: 700; letter-spacing: 0.08em;
            color: #4d7fa0; text-transform: uppercase; padding: 10px 14px 4px;
        }

        /* Scrollbar thin */
        .slim-scroll::-webkit-scrollbar { width: 4px; }
        .slim-scroll::-webkit-scrollbar-track { background: transparent; }
        .slim-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.12); border-radius: 4px; }

        /* Status badges */
        .status-available       { background:#dcfce7; color:#166534; }
        .status-reserved        { background:#dbeafe; color:#1e40af; }
        .status-occupied        { background:#fee2e2; color:#991b1b; }
        .status-under_inspection{ background:#fef9c3; color:#854d0e; }
        .status-maintenance     { background:#f3f4f6; color:#374151; }
    </style>
    @stack('styles')
</head>
<body class="bg-slate-50 text-gray-800">
<div x-data="{ sidebarOpen: true }" class="flex h-screen overflow-hidden">

    <!-- Sidebar -->
    <aside :class="sidebarOpen ? 'w-60' : 'w-0 overflow-hidden'"
           class="flex-shrink-0 flex flex-col transition-all duration-300 select-none"
           style="background: linear-gradient(180deg, #0d3f64 0%, #0a3254 100%); box-shadow: 2px 0 12px rgba(0,0,0,0.18);">

        <!-- Logo -->
        <div class="px-5 py-5 flex-shrink-0" style="border-bottom: 1px solid rgba(255,255,255,0.07);">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0 font-bold text-primary-900 text-base"
                     style="background: linear-gradient(135deg, #D4A574, #c08d5a);">س</div>
                <div class="min-w-0">
                    <div class="font-bold text-sm text-white leading-tight truncate">الفندق السعودي</div>
                    <div class="text-xs truncate" style="color:#4d7fa0;">نظام إدارة الفندق</div>
                </div>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 px-3 py-3 overflow-y-auto slim-scroll space-y-0.5">

            <!-- رئيسي -->
            <div class="nav-section-label">رئيسي</div>

            @can('dashboard.view')
            <a href="{{ route('dashboard') }}"
               class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                لوحة التحكم
            </a>
            @endcan

@can('checkin.view')
            <a href="{{ route('reservations.index') }}"
               class="nav-link {{ request()->routeIs('reservations.*') ? 'active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                الحجوزات
            </a>
            @endcan

            <!-- إدارة -->
            <div class="nav-section-label mt-2">إدارة</div>

            @can('rooms.view')
            <a href="{{ route('rooms.index') }}"
               class="nav-link {{ request()->routeIs('rooms.*') ? 'active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                الغرف
            </a>
            @endcan

            @can('rooms.manage')
            <a href="{{ route('floors.index') }}"
               class="nav-link {{ request()->routeIs('floors.*') ? 'active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18M10 4v16M14 4v16"/></svg>
                الطوابق
            </a>
            @endcan

            @can('shifts.view')
            <a href="{{ route('shifts.index') }}"
               class="nav-link {{ request()->routeIs('shifts.*') ? 'active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                الورديات
            </a>
            @endcan

            @can('settlement.view')
            <a href="{{ route('settlement.index') }}"
               class="nav-link {{ request()->routeIs('settlement.*') ? 'active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                التسوية النقدية
            </a>
            @endcan

            <!-- تقارير -->
            @can('reports.view')
            <div class="nav-section-label mt-2">تقارير</div>
            <div x-data="{ open: {{ request()->routeIs('reports.*') ? 'true' : 'false' }} }">
                <button @click="open=!open"
                        class="nav-link w-full justify-between {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                    <span class="flex items-center gap-2.5">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        التقارير
                    </span>
                    <svg :class="open ? 'rotate-180' : ''" class="w-3.5 h-3.5 flex-shrink-0 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" x-cloak class="mt-0.5 mb-1 mr-5 space-y-0.5 border-r border-white/10 pr-2">
                    <a href="{{ route('reports.occupancy') }}" class="nav-link text-xs {{ request()->routeIs('reports.occupancy') ? 'active' : '' }}">
                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>
                        إشغال الغرف
                    </a>
                    <a href="{{ route('reports.revenue') }}" class="nav-link text-xs {{ request()->routeIs('reports.revenue') ? 'active' : '' }}">
                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        الإيرادات
                    </a>
                    <a href="{{ route('reports.staff') }}" class="nav-link text-xs {{ request()->routeIs('reports.staff') ? 'active' : '' }}">
                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        أداء الموظفين
                    </a>
                    <a href="{{ route('reports.daily') }}" class="nav-link text-xs {{ request()->routeIs('reports.daily') ? 'active' : '' }}">
                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        القائمة اليومية
                    </a>
                    <a href="{{ route('reports.reservations') }}" class="nav-link text-xs {{ request()->routeIs('reports.reservations') ? 'active' : '' }}">
                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        تقرير الحجوزات
                    </a>
                    <a href="{{ route('reports.shifts') }}" class="nav-link text-xs {{ request()->routeIs('reports.shifts') ? 'active' : '' }}">
                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        تقرير الورديات
                    </a>
                </div>
            </div>
            @endcan

            <!-- النظام -->
            @can('users.manage')
            <div class="nav-section-label mt-2">النظام</div>
            <a href="{{ route('users.index') }}"
               class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                المستخدمون
            </a>
            @endcan

            @can('audit_log.view')
            <a href="{{ route('audit.log') }}"
               class="nav-link {{ request()->routeIs('audit.*') ? 'active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                سجل المراجعة
            </a>
            @endcan

            <!-- الموارد البشرية -->
            @can('hr.view')
            <div class="nav-section-label mt-2">الموارد البشرية</div>
            <div x-data="{ open: {{ request()->routeIs('employees.*','salaries.*') ? 'true' : 'false' }} }">
                <button @click="open=!open"
                        class="nav-link w-full justify-between {{ request()->routeIs('employees.*','salaries.*') ? 'active' : '' }}">
                    <span class="flex items-center gap-2.5">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        الموارد البشرية
                    </span>
                    <svg :class="open ? 'rotate-180' : ''" class="w-3.5 h-3.5 flex-shrink-0 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" x-cloak class="mt-0.5 mb-1 mr-5 space-y-0.5 border-r border-white/10 pr-2">
                    <a href="{{ route('employees.index') }}" class="nav-link text-xs {{ request()->routeIs('employees.*') ? 'active' : '' }}">
                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        الموظفون
                    </a>
                    <a href="{{ route('salaries.index') }}" class="nav-link text-xs {{ request()->routeIs('salaries.*') ? 'active' : '' }}">
                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        الرواتب
                    </a>
                </div>
            </div>
            @endcan

            <!-- إدارة المصروفات -->
            @can('expenses.view')
            <div class="nav-section-label mt-2">المصروفات</div>
            <div x-data="{ open: {{ request()->routeIs('expenses.*','suppliers.*') ? 'true' : 'false' }} }">
                <button @click="open=!open"
                        class="nav-link w-full justify-between {{ request()->routeIs('expenses.*','suppliers.*') ? 'active' : '' }}">
                    <span class="flex items-center gap-2.5">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        إدارة المصروفات
                    </span>
                    <svg :class="open ? 'rotate-180' : ''" class="w-3.5 h-3.5 flex-shrink-0 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" x-cloak class="mt-0.5 mb-1 mr-5 space-y-0.5 border-r border-white/10 pr-2">
                    <a href="{{ route('expenses.index') }}" class="nav-link text-xs {{ request()->routeIs('expenses.index') || request()->routeIs('expenses.create') || request()->routeIs('expenses.edit') ? 'active' : '' }}">
                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        المصروفات
                    </a>
                    <a href="{{ route('expenses.report') }}" class="nav-link text-xs {{ request()->routeIs('expenses.report') ? 'active' : '' }}">
                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        تقرير المصروفات
                    </a>
                    <a href="{{ route('suppliers.index') }}" class="nav-link text-xs {{ request()->routeIs('suppliers.*') ? 'active' : '' }}">
                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        الموردون
                    </a>
                </div>
            </div>
            @endcan
        </nav>

        <!-- User info -->
        <div class="px-4 py-3 flex-shrink-0" style="border-top: 1px solid rgba(255,255,255,0.07);">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center font-bold text-sm flex-shrink-0 text-primary-900"
                     style="background: linear-gradient(135deg, #D4A574, #c08d5a);">
                    {{ mb_substr(auth()->user()->name, 0, 1) }}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-xs font-semibold text-white truncate">{{ auth()->user()->name }}</div>
                    <div class="text-xs truncate" style="color:#4d7fa0;">{{ auth()->user()->roles->first()?->name ?? 'مستخدم' }}</div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-7 h-7 rounded-lg flex items-center justify-center transition-colors hover:bg-white/10" title="تسجيل الخروج" style="color:#4d7fa0;">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- Main content -->
    <div class="flex-1 flex flex-col overflow-hidden">

        <!-- Top bar -->
        <header class="bg-white flex-shrink-0 flex items-center gap-4 px-6 py-3.5"
                style="border-bottom: 1px solid #e8edf2; box-shadow: 0 1px 4px rgba(0,0,0,0.04);">
            <button @click="sidebarOpen=!sidebarOpen"
                    class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <h1 class="text-base font-semibold text-gray-800 flex-1">@yield('page-title', 'لوحة التحكم')</h1>
            <div class="flex items-center gap-3">
                <span class="text-xs text-gray-400 hidden sm:block">{{ now()->isoFormat('dddd، D MMMM Y') }}</span>
                <div class="w-px h-5 bg-gray-200 hidden sm:block"></div>
                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold"
                      style="background:#e8f0f7; color:#0F4C75;">
                    {{ auth()->user()->roles->first()?->name ?? '' }}
                </span>
            </div>
        </header>

        <!-- Flash messages -->
        @if(session('success'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show=false, 4500)"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-end="opacity-0"
             class="mx-6 mt-4 p-3.5 bg-green-50 border border-green-200 rounded-xl flex items-center gap-3 text-green-800 text-sm shadow-sm">
            <div class="w-5 h-5 bg-green-500 rounded-full flex items-center justify-center flex-shrink-0">
                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
            </div>
            <span class="flex-1">{{ session('success') }}</span>
            <button @click="show=false" class="text-green-400 hover:text-green-600 ml-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        @endif

        @if(session('error'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show=false, 5000)"
             class="mx-6 mt-4 p-3.5 bg-red-50 border border-red-200 rounded-xl flex items-center gap-3 text-red-800 text-sm shadow-sm">
            <div class="w-5 h-5 bg-red-500 rounded-full flex items-center justify-center flex-shrink-0">
                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
            </div>
            <span class="flex-1">{{ session('error') }}</span>
        </div>
        @endif

        @if($errors->any())
        <div class="mx-6 mt-4 p-3.5 bg-red-50 border border-red-200 rounded-xl text-red-800 text-sm shadow-sm">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <!-- Page Content -->
        <main class="flex-1 overflow-y-auto p-6">
            @yield('content')
        </main>
    </div>
</div>
@stack('scripts')
</body>
</html>
