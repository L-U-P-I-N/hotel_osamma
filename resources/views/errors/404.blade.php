<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head><meta charset="UTF-8"><title>404 - غير موجود</title>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<style>body{font-family:'Cairo',sans-serif;}</style>
</head>
<body class="min-h-screen bg-gray-50 flex items-center justify-center">
    <div class="text-center">
        <div class="text-9xl font-bold text-blue-200 mb-4">404</div>
        <h1 class="text-2xl font-bold text-gray-800 mb-2">الصفحة غير موجودة</h1>
        <p class="text-gray-500 mb-6">الصفحة التي تبحث عنها غير موجودة أو تم نقلها</p>
        <a href="{{ url('/dashboard') }}" class="bg-primary-800 text-white px-6 py-2 rounded-lg hover:bg-primary-700 transition" style="background:#0F4C75">العودة للرئيسية</a>
    </div>
</body>
</html>
