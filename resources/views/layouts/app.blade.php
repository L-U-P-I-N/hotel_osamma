<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>@yield('title', 'الفندق السعودي') - نظام إدارة الفندق</title>

    {{-- Theme (الوضع الليلي/النهاري) — applied before paint to avoid flash --}}
    <script>
        (function () {
            try {
                var t = localStorage.getItem('theme');
                if (!t) t = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                if (t === 'dark') document.documentElement.classList.add('dark');
            } catch (e) {}
        })();
        function toggleTheme() {
            var isDark = document.documentElement.classList.toggle('dark');
            try { localStorage.setItem('theme', isDark ? 'dark' : 'light'); } catch (e) {}
            var meta = document.querySelector('meta[name="theme-color"]');
            if (meta) meta.setAttribute('content', isDark ? '#0f172a' : '#0F4C75');
        }
        // Back navigation — essential in PWA standalone mode (no browser back button)
        function goBack() {
            if (window.history.length > 1) { window.history.back(); }
            else { window.location.href = @json(route('dashboard')); }
        }
    </script>

    <!-- PWA -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#0F4C75">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="فندق السعودي">
    <link rel="apple-touch-icon" href="/icons/icon-192x192.png">
    <link rel="icon" type="image/png" sizes="192x192" href="/icons/icon-192x192.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
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
    {{-- Flatpickr — date picker that supports typing OR calendar selection (loaded before Alpine) --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/ar.js"></script>
    <script>
        // Apply Arabic locale globally (runs synchronously, before Alpine's deferred init)
        if (window.flatpickr && flatpickr.l10ns && flatpickr.l10ns.ar) {
            flatpickr.localize(flatpickr.l10ns.ar);
        }

        // ── توحيد كل حقول التاريخ في النظام لتُعرض بالإنجليزية dd/mm/yyyy ──
        // يحوّل كل <input type="date"> إلى منتقي flatpickr يعرض d/m/Y بينما
        // يُرسل للخادم القيمة الآمنة Y-m-d، ويطلق حدثَي input + change حتى تعمل
        // روابط Alpine (x-model) ومرشّحات onchange="this.form.submit()" كما هي.
        (function () {
            function convertDateInput(el) {
                if (typeof window.flatpickr === 'undefined' || el.dataset.fpInit) return;
                el.dataset.fpInit = '1';
                var opts = {
                    allowInput: true,
                    dateFormat: 'Y-m-d',
                    altInput: true,
                    altFormat: 'd/m/Y',
                    disableMobile: true,
                    onChange: function (selected, str, inst) {
                        inst.input.dispatchEvent(new Event('input',  { bubbles: true }));
                        inst.input.dispatchEvent(new Event('change', { bubbles: true }));
                    },
                };
                if (el.getAttribute('min')) opts.minDate = el.getAttribute('min');
                if (el.getAttribute('max')) opts.maxDate = el.getAttribute('max');
                var fp = window.flatpickr(el, opts);
                if (fp && fp.altInput) fp.altInput.setAttribute('placeholder', 'dd/mm/yyyy');
            }
            function scan(root) {
                (root || document).querySelectorAll('input[type="date"]:not([data-fp-init])').forEach(convertDateInput);
            }
            // مسح أولي بعد جاهزية Alpine (لعناصر x-for) ثم احتياطي على DOMContentLoaded
            document.addEventListener('alpine:initialized', function () { scan(); });
            document.addEventListener('DOMContentLoaded', function () {
                scan();
                // التقاط الحقول المُضافة ديناميكياً (مرافقون، نوافذ منبثقة…)
                var mo = new MutationObserver(function (muts) {
                    muts.forEach(function (m) {
                        m.addedNodes.forEach(function (n) {
                            if (n.nodeType !== 1) return;
                            if (n.matches && n.matches('input[type="date"]')) convertDateInput(n);
                            else if (n.querySelectorAll) scan(n);
                        });
                    });
                });
                if (document.body) mo.observe(document.body, { childList: true, subtree: true });
            });
        })();
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="/css/app-theme.css?v={{ @filemtime(public_path('css/app-theme.css')) ?: '1' }}">
    @stack('styles')
</head>
<body class="bg-slate-50 text-gray-800">

<!-- PWA Install Banner (Chrome/Edge/Android) -->
<div id="pwa-install-banner" class="hidden fixed bottom-4 left-1/2 -translate-x-1/2 z-50 flex items-center gap-3 px-5 py-3 rounded-2xl shadow-xl text-white text-sm font-medium" style="background:#0F4C75; min-width:280px; max-width:90vw;">
    <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
    <span class="flex-1">تثبيت التطبيق على جهازك</span>
    <button onclick="installPWA()" class="px-3 py-1 rounded-lg text-xs font-bold transition flex-shrink-0" style="background:rgba(255,255,255,0.2);">تثبيت</button>
    <button onclick="document.getElementById('pwa-install-banner').classList.add('hidden')" class="text-white/60 hover:text-white flex-shrink-0">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
    </button>
</div>

<!-- PWA Install Banner (Safari iOS/iPadOS) -->
<div id="pwa-safari-banner" class="hidden fixed bottom-4 left-1/2 -translate-x-1/2 z-50 rounded-2xl shadow-xl text-white text-sm" style="background:#0F4C75; min-width:300px; max-width:92vw;">
    <div class="flex items-center gap-3 px-4 pt-4 pb-2">
        <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
        <span class="flex-1 font-medium">تثبيت التطبيق على جهازك</span>
        <button onclick="document.getElementById('pwa-safari-banner').classList.add('hidden')" class="text-white/60 hover:text-white flex-shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
    <div class="px-4 pb-4 text-xs" style="color:rgba(255,255,255,0.8);">
        <p class="mb-1">اضغط على زر المشاركة
            <svg class="w-4 h-4 inline-block mx-0.5 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
            في أسفل الشاشة
        </p>
        <p>ثم اختر <strong>"إضافة إلى الشاشة الرئيسية"</strong></p>
    </div>
    <!-- مثلث يشير للأسفل -->
    <div class="flex justify-center pb-1">
        <svg class="w-5 h-5 text-blue-900" fill="currentColor" viewBox="0 0 20 20" style="color:#0a3254;"><path d="M10 15l-8-8h16l-8 8z"/></svg>
    </div>
</div>

<div x-data="{ sidebarOpen: window.innerWidth >= 1024 }" class="flex h-screen overflow-hidden">

    <!-- Sidebar -->
    <aside :class="sidebarOpen ? 'w-60' : 'w-0 overflow-hidden'"
           class="flex-shrink-0 flex flex-col transition-all duration-300 select-none"
           style="background: linear-gradient(180deg, #0d3f64 0%, #0a3254 100%); box-shadow: 2px 0 12px rgba(0,0,0,0.18);">

        <!-- Logo -->
        <div class="px-5 py-5 flex-shrink-0" style="border-bottom: 1px solid rgba(255,255,255,0.07);">
            <div class="flex items-center gap-3">
                @if(file_exists(public_path('images/hotel-logo.png')))
                <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0 overflow-hidden bg-white">
                    <img src="{{ asset('images/hotel-logo.png') }}" alt="شعار الفندق" class="w-full h-full object-contain">
                </div>
                @else
                <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0 font-bold text-primary-900 text-base"
                     style="background: linear-gradient(135deg, #D4A574, #c08d5a);">س</div>
                @endif
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
            @php $_overdueCount = \App\Models\Reservation::where('status','checked_in')->whereDate('check_out_date','<=',today())->count(); @endphp
            <a href="{{ route('reservations.expiring') }}"
               class="nav-link {{ request()->routeIs('reservations.*') ? 'active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                الحجوزات
                @if($_overdueCount > 0)
                <span class="mr-auto bg-red-500 text-white text-[9px] font-bold rounded-full min-w-[18px] h-[18px] flex items-center justify-center px-1 flex-shrink-0 animate-pulse">
                    {{ $_overdueCount }}
                </span>
                @endif
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

            @canany(['rooms.create','rooms.edit','rooms.delete'])
            <a href="{{ route('floors.index') }}"
               class="nav-link {{ request()->routeIs('floors.*') ? 'active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18M10 4v16M14 4v16"/></svg>
                الطوابق
            </a>
            @endcanany

            @can('shifts.view')
            <a href="{{ route('shifts.index') }}"
               class="nav-link {{ request()->routeIs('shifts.*') ? 'active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                الورديات
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
                    <a href="{{ route('reports.dailyHub') }}" class="nav-link text-xs {{ request()->routeIs('reports.dailyHub') ? 'active' : '' }}">
                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        التشغيل اليومي
                    </a>
                    <a href="{{ route('reports.hrHub') }}" class="nav-link text-xs {{ request()->routeIs('reports.hrHub') ? 'active' : '' }}">
                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        الموارد البشرية
                    </a>
                    <a href="{{ route('reports.shiftsHub') }}" class="nav-link text-xs {{ request()->routeIs('reports.shiftsHub') ? 'active' : '' }}">
                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        الورديات والصناديق
                    </a>
                    <a href="{{ route('reports.guestsRoomsHub') }}" class="nav-link text-xs {{ request()->routeIs('reports.guestsRoomsHub') ? 'active' : '' }}">
                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        الضيوف والغرف
                    </a>
                    <a href="{{ route('reports.debts') }}" class="nav-link text-xs {{ request()->routeIs('reports.debts') ? 'active' : '' }}">
                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        تقرير الديون
                    </a>
                    <a href="{{ route('reports.partialPayments') }}" class="nav-link text-xs {{ request()->routeIs('reports.partialPayments') ? 'active' : '' }}">
                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        الدفعات الجزئية
                    </a>
                    <a href="{{ route('reports.financeHub') }}" class="nav-link text-xs {{ request()->routeIs('reports.financeHub') ? 'active' : '' }}">
                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        التقارير المالية
                    </a>
                    <a href="{{ route('reports.profitLoss') }}" class="nav-link text-xs {{ request()->routeIs('reports.profitLoss') ? 'active' : '' }}">
                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        الأرباح والخسائر
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
            @canany(['hr.view','attendance.view'])
            <div class="nav-section-label mt-2">الموارد البشرية</div>
            <div x-data="{ open: {{ request()->routeIs('employees.*','salaries.*','attendance.*','leaves.*') ? 'true' : 'false' }} }">
                <button @click="open=!open"
                        class="nav-link w-full justify-between {{ request()->routeIs('employees.*','salaries.*','attendance.*','leaves.*') ? 'active' : '' }}">
                    <span class="flex items-center gap-2.5">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        الموارد البشرية
                    </span>
                    <svg :class="open ? 'rotate-180' : ''" class="w-3.5 h-3.5 flex-shrink-0 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" x-cloak class="mt-0.5 mb-1 mr-5 space-y-0.5 border-r border-white/10 pr-2">
                    @can('hr.view')
                    <a href="{{ route('employees.index') }}" class="nav-link text-xs {{ request()->routeIs('employees.*') ? 'active' : '' }}">
                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        الموظفون
                    </a>
                    <a href="{{ route('salaries.index') }}" class="nav-link text-xs {{ request()->routeIs('salaries.*') ? 'active' : '' }}">
                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        الرواتب
                    </a>
                    <a href="{{ route('leaves.index') }}" class="nav-link text-xs {{ request()->routeIs('leaves.*') ? 'active' : '' }}">
                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        الإجازات
                    </a>
                    @endcan
                    @can('attendance.view')
                    <a href="{{ route('attendance.index') }}" class="nav-link text-xs {{ request()->routeIs('attendance.*') ? 'active' : '' }}">
                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                        الحضور والغياب
                    </a>
                    @endcan
                </div>
            </div>
            @endcanany

            <!-- إدارة المصروفات -->
            @can('expenses.view')
            <div class="nav-section-label mt-2">المصروفات</div>
            <div x-data="{ open: {{ request()->routeIs('expenses.*') ? 'true' : 'false' }} }">
                <button @click="open=!open"
                        class="nav-link w-full justify-between {{ request()->routeIs('expenses.*') ? 'active' : '' }}">
                    <span class="flex items-center gap-2.5">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        إدارة المصروفات
                    </span>
                    <svg :class="open ? 'rotate-180' : ''" class="w-3.5 h-3.5 flex-shrink-0 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" x-cloak class="mt-0.5 mb-1 mr-5 space-y-0.5 border-r border-white/10 pr-2">
                    <a href="{{ route('expenses.index') }}" class="nav-link text-xs {{ request()->routeIs('expenses.index','expenses.create','expenses.edit') ? 'active' : '' }}">
                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        المصروفات
                    </a>
                    <a href="{{ route('expenses.deferred') }}" class="nav-link text-xs {{ request()->routeIs('expenses.deferred') ? 'active' : '' }}">
                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        المصروفات الآجلة
                    </a>
                </div>
            </div>
            @endcan
        </nav>

        <!-- PWA Install Button (sidebar) -->
        <div id="pwa-sidebar-install" class="px-3 pb-2 flex-shrink-0">
            <button onclick="installPWA()" id="pwa-sidebar-btn"
                    class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-medium transition-colors"
                    style="background:rgba(255,255,255,0.08); color:#a8c8e0;">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                <span id="pwa-sidebar-label">تثبيت التطبيق</span>
            </button>
            <!-- رسالة مساعدة للمتصفحات التي لا يتوفر فيها الطلب التلقائي -->
            <div id="pwa-manual-hint" class="hidden mt-1.5 px-2 py-1.5 rounded-lg text-xs" style="background:rgba(255,255,255,0.05);color:#6ea3c0;line-height:1.5;">
                <span id="pwa-hint-text"></span>
            </div>
        </div>

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
        <header class="app-topbar bg-white flex-shrink-0 flex items-center gap-4 px-6 py-3.5"
                style="border-bottom: 1px solid #e8edf2; box-shadow: 0 1px 4px rgba(0,0,0,0.04);">
            <button @click="sidebarOpen=!sidebarOpen"
                    class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            {{-- Back button — always available (critical in PWA standalone where there's no browser back) --}}
            @hasSection('back-url')
            <a href="@yield('back-url')"
               class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-500 hover:text-gray-700 hover:bg-gray-100 transition-colors flex-shrink-0"
               title="رجوع">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
            @else
            @unless(request()->routeIs('dashboard'))
            <button type="button" onclick="goBack()"
               class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-500 hover:text-gray-700 hover:bg-gray-100 transition-colors flex-shrink-0"
               title="رجوع">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
            @endunless
            @endif
            <h1 class="text-base font-semibold text-gray-800 flex-1">@yield('page-title', 'لوحة التحكم')</h1>
            <div class="flex items-center gap-3">
                {{-- Refresh — essential in PWA standalone mode (no browser reload button) --}}
                <button type="button" onclick="this.firstElementChild.classList.add('animate-spin'); window.location.reload()"
                        title="تحديث الصفحة"
                        class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                </button>
                {{-- Theme toggle (الوضع الليلي/النهاري) --}}
                <button type="button" onclick="toggleTheme()"
                        title="تبديل الوضع الليلي / النهاري"
                        class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors">
                    {{-- moon: shown in light mode (click → dark) --}}
                    <svg class="theme-icon-moon w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                    {{-- sun: shown in dark mode (click → light) --}}
                    <svg class="theme-icon-sun w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </button>
                <div class="w-px h-5 bg-gray-200 hidden sm:block"></div>
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

<!-- BFCache: reload when page is restored from browser memory so data is always fresh -->
<script>
window.addEventListener('pageshow', function(e) {
    if (e.persisted) {
        window.location.reload();
    }
});
</script>

<!-- PWA Service Worker + Install Prompt -->
<script>
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/sw.js');
}

let deferredPrompt = null;

const isIos       = /iphone|ipad|ipod/i.test(navigator.userAgent);
const isSafari    = /^((?!chrome|android).)*safari/i.test(navigator.userAgent);
const isIosSafari = isIos && isSafari;
const isStandalone = window.matchMedia('(display-mode: standalone)').matches
                   || window.navigator.standalone === true;

// إذا التطبيق مثبّت بالفعل → أخفِ كل عناصر التثبيت
if (isStandalone) {
    document.getElementById('pwa-sidebar-install')?.classList.add('hidden');
}

// Chrome/Edge/Android — احتجز الحدث قبل ظهور نافذة التثبيت التلقائية
window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;
    // أظهر البانر الكبير عند أول مرة فقط
    document.getElementById('pwa-install-banner')?.classList.remove('hidden');
});

function installPWA() {
    // Safari iOS — أظهر التعليمات اليدوية
    if (isIosSafari) {
        const hint  = document.getElementById('pwa-manual-hint');
        const htext = document.getElementById('pwa-hint-text');
        if (hint && htext) {
            htext.textContent = 'اضغط على زر المشاركة ثم اختر "إضافة إلى الشاشة الرئيسية"';
            hint.classList.toggle('hidden');
        }
        document.getElementById('pwa-safari-banner')?.classList.remove('hidden');
        return;
    }

    // Chrome/Edge — يوجد prompt محفوظ → افتحه مباشرة
    if (deferredPrompt) {
        deferredPrompt.prompt();
        deferredPrompt.userChoice.then((choice) => {
            deferredPrompt = null;
            document.getElementById('pwa-install-banner')?.classList.add('hidden');
            if (choice.outcome === 'accepted') {
                document.getElementById('pwa-sidebar-install')?.classList.add('hidden');
            }
        });
        return;
    }

    // لا يوجد prompt محفوظ (المستخدم رفض سابقاً أو المتصفح لا يدعمه)
    // → أظهر تعليمة تشير لأيقونة التثبيت في شريط العنوان
    const hint  = document.getElementById('pwa-manual-hint');
    const htext = document.getElementById('pwa-hint-text');
    if (!hint || !htext) return;
    const isChromeLike = /chrome|chromium|edg/i.test(navigator.userAgent) && !isIos;
    htext.innerHTML = isChromeLike
        ? 'ابحث عن أيقونة <strong>⊕</strong> في شريط العنوان ثم اضغط عليها للتثبيت، أو أعد تحميل الصفحة.'
        : 'افتح قائمة المتصفح واختر "تثبيت التطبيق" أو "إضافة إلى الشاشة الرئيسية".';
    hint.classList.remove('hidden');
    // أخفِ الرسالة تلقائياً بعد 6 ثوانٍ
    setTimeout(() => hint.classList.add('hidden'), 6000);
}

// إذا ظهر بانر Safari بعد 2 ثانية للمستخدمين الجدد
if (isIosSafari && !isStandalone) {
    setTimeout(() => {
        document.getElementById('pwa-safari-banner')?.classList.remove('hidden');
    }, 2500);
}

window.addEventListener('appinstalled', () => {
    deferredPrompt = null;
    document.getElementById('pwa-install-banner')?.classList.add('hidden');
    document.getElementById('pwa-safari-banner')?.classList.add('hidden');
    document.getElementById('pwa-sidebar-install')?.classList.add('hidden');
});
</script>

<!-- Toast Notifications System -->
@include('components.toast-notification')
@include('components.delete-confirmation-modal')

<!-- Flash messages → Toast -->
@if(session('success'))
<script>
    document.addEventListener('DOMContentLoaded', function() {
        Toast.success('{{ session("success") }}');
    });
</script>
@endif

@if(session('error'))
<script>
    document.addEventListener('DOMContentLoaded', function() {
        Toast.error('{{ session("error") }}');
    });
</script>
@endif

</body>
</html>
