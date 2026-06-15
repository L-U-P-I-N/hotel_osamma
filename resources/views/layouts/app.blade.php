<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'فندق أسامة') - نظام إدارة الفندق</title>
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
                        accent: { DEFAULT: '#D4A574', 50:'#fdf6ee', 100:'#f9e8d2', 200:'#f3d5b0', 300:'#ecc18e', 400:'#e6ae6c', 500:'#D4A574', 600:'#c08d5a', 700:'#a67040', 800:'#8c5530', 900:'#6e3f22' },
                    }
                }
            }
        }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: 'Cairo', sans-serif; }
        .sidebar-link { @apply flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-200; }
        .sidebar-link:hover { @apply bg-primary-700 text-white; }
        .sidebar-link.active { @apply bg-primary-700 text-white; }
        .status-available { @apply bg-green-100 text-green-800; }
        .status-reserved { @apply bg-blue-100 text-blue-800; }
        .status-occupied { @apply bg-red-100 text-red-800; }
        .status-under_inspection { @apply bg-yellow-100 text-yellow-800; }
        .status-maintenance { @apply bg-gray-100 text-gray-800; }
    </style>
    @stack('styles')
</head>
<body class="bg-gray-50 text-gray-800">
<div x-data="{ sidebarOpen: true }" class="flex h-screen overflow-hidden">

    <!-- Sidebar -->
    <aside :class="sidebarOpen ? 'w-64' : 'w-0 overflow-hidden'" class="flex-shrink-0 bg-primary-800 text-white flex flex-col transition-all duration-300">
        <!-- Logo -->
        <div class="px-6 py-5 border-b border-primary-700">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-accent rounded-lg flex items-center justify-center font-bold text-primary-900 text-lg">ف</div>
                <div>
                    <div class="font-bold text-sm leading-tight">فندق أسامة</div>
                    <div class="text-primary-300 text-xs">نظام إدارة الفندق</div>
                </div>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
            @can('dashboard.view')
            <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : 'text-primary-200' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                لوحة التحكم
            </a>
            @endcan

            @can('rooms.view')
            <a href="{{ route('rooms.index') }}" class="sidebar-link {{ request()->routeIs('rooms.*') ? 'active' : 'text-primary-200' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                الغرف
            </a>
            @endcan

            @can('checkin.create')
            <a href="{{ route('checkin.create') }}" class="sidebar-link {{ request()->routeIs('checkin.create') ? 'active' : 'text-primary-200' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                تسجيل دخول
            </a>
            @endcan

            @can('checkin.view')
            <a href="{{ route('reservations.index') }}" class="sidebar-link {{ request()->routeIs('reservations.*') ? 'active' : 'text-primary-200' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                الحجوزات
            </a>
            @endcan

            @can('payments.view')
            <a href="{{ route('settlement.index') }}" class="sidebar-link {{ request()->routeIs('settlement.*') ? 'active' : 'text-primary-200' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                التسوية النقدية
            </a>
            @endcan

            @can('reports.view')
            <div x-data="{ open: {{ request()->routeIs('reports.*') ? 'true' : 'false' }} }">
                <button @click="open=!open" class="sidebar-link text-primary-200 w-full justify-between">
                    <span class="flex items-center gap-3">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        التقارير
                    </span>
                    <svg :class="open ? 'rotate-180' : ''" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" class="mr-4 mt-1 space-y-1">
                    <a href="{{ route('reports.occupancy') }}" class="sidebar-link text-primary-300 text-xs">إشغال الغرف</a>
                    <a href="{{ route('reports.revenue') }}" class="sidebar-link text-primary-300 text-xs">الإيرادات</a>
                    <a href="{{ route('reports.staff') }}" class="sidebar-link text-primary-300 text-xs">أداء الموظفين</a>
                </div>
            </div>
            @endcan

            @can('blacklist.manage')
            <a href="{{ route('blacklist.index') }}" class="sidebar-link {{ request()->routeIs('blacklist.*') ? 'active' : 'text-primary-200' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                القائمة السوداء
            </a>
            @endcan

            @can('users.manage')
            <a href="{{ route('users.index') }}" class="sidebar-link {{ request()->routeIs('users.*') ? 'active' : 'text-primary-200' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                المستخدمون
            </a>
            @endcan

            @can('audit_log.view')
            <a href="{{ route('audit.log') }}" class="sidebar-link {{ request()->routeIs('audit.*') ? 'active' : 'text-primary-200' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                سجل المراجعة
            </a>
            @endcan
        </nav>

        <!-- User info -->
        <div class="px-4 py-4 border-t border-primary-700">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-accent rounded-full flex items-center justify-center text-primary-900 font-bold text-sm">
                    {{ mb_substr(auth()->user()->name, 0, 1) }}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-medium truncate">{{ auth()->user()->name }}</div>
                    <div class="text-primary-300 text-xs">{{ auth()->user()->roles->first()?->name ?? 'مستخدم' }}</div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-primary-300 hover:text-white" title="تسجيل الخروج">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- Main -->
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Top bar -->
        <header class="bg-white border-b border-gray-200 px-6 py-3 flex items-center gap-4 flex-shrink-0">
            <button @click="sidebarOpen=!sidebarOpen" class="text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <h1 class="text-lg font-semibold text-gray-800 flex-1">@yield('page-title', 'لوحة التحكم')</h1>
            <div class="flex items-center gap-3">
                <span class="text-sm text-gray-500">{{ now()->format('d/m/Y') }}</span>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-800">
                    {{ auth()->user()->roles->first()?->name ?? '' }}
                </span>
            </div>
        </header>

        <!-- Flash messages -->
        @if(session('success'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show=false, 4000)"
             class="mx-6 mt-4 p-4 bg-green-50 border border-green-200 rounded-lg flex items-center gap-3 text-green-800 text-sm">
            <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            {{ session('success') }}
        </div>
        @endif

        @if($errors->any())
        <div x-data="{ show: true }" x-show="show"
             class="mx-6 mt-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm">
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
