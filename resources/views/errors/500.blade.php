<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head><meta charset="UTF-8"><title>500 - خطأ في الخادم</title>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<style>body{font-family:'Cairo',sans-serif;}</style>
</head>
<body class="min-h-screen bg-gray-50 flex items-center justify-center p-4">
    <div class="text-center max-w-2xl w-full">
        <div class="text-9xl font-bold text-gray-200 mb-4">500</div>
        <h1 class="text-2xl font-bold text-gray-800 mb-2">خطأ في الخادم</h1>
        <p class="text-gray-500 mb-6">حدث خطأ غير متوقع. يرجى المحاولة مرة أخرى لاحقاً</p>

        @if(config('app.debug') && isset($exception))
        <div class="text-left bg-red-50 border border-red-200 rounded-lg p-4 mb-6 text-sm">
            <p class="font-bold text-red-700 mb-2">{{ get_class($exception) }}</p>
            <p class="text-red-600 mb-2">{{ $exception->getMessage() }}</p>
            <p class="text-gray-500 text-xs">{{ $exception->getFile() }}:{{ $exception->getLine() }}</p>
        </div>
        @endif

        <a href="{{ url('/dashboard') }}" class="text-white px-6 py-2 rounded-lg transition" style="background:#0F4C75">العودة للرئيسية</a>
    </div>
</body>
</html>
