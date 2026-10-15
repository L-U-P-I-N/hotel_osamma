<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head><meta charset="UTF-8"><title>503 - النظام تحت الصيانة</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
<style>body{font-family:'Cairo',sans-serif;}</style>
</head>
<body class="min-h-screen bg-gray-50 flex items-center justify-center p-4">
    <div class="text-center max-w-md">
        <div class="text-9xl font-bold text-slate-300 mb-4">503</div>
        <h1 class="text-2xl font-bold text-gray-800 mb-2">النظام تحت الصيانة</h1>
        <p class="text-gray-500 mb-6">{{ $friendly ?? 'النظام متوقف مؤقتاً للصيانة. أعد المحاولة بعد قليل.' }}</p>
        <a href="{{ url('/dashboard') }}" class="text-white px-6 py-2 rounded-lg transition inline-block" style="background:var(--ui-accent);color:var(--ui-accent-fg)">العودة للرئيسية</a>
    </div>
</body>
</html>
