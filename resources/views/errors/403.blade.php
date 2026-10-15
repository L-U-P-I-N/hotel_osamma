<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head><meta charset="UTF-8"><title>403 - غير مصرح</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
<style>body{font-family:'Cairo',sans-serif;}</style>
</head>
<body class="min-h-screen bg-gray-50 flex items-center justify-center">
    <div class="text-center">
        <div class="text-9xl font-bold text-red-200 mb-4">403</div>
        <h1 class="text-2xl font-bold text-gray-800 mb-2">غير مصرح بالوصول</h1>
        <p class="text-gray-500 mb-6">{{ $friendly ?? 'ليست لديك صلاحية لهذه العملية — راجع مدير النظام لمنحك الصلاحية.' }}</p>
        <a href="{{ url('/dashboard') }}" class="bg-primary-800 text-white px-6 py-2 rounded-lg hover:bg-primary-700 transition" style="background:var(--ui-accent);color:var(--ui-accent-fg)">العودة للرئيسية</a>
    </div>
</body>
</html>
