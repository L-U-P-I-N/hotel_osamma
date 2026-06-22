<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - غير موجود</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>body { font-family: 'Cairo', sans-serif; }</style>
    <!-- إعادة توجيه تلقائية للداشبورد بعد 4 ثوانٍ -->
    <meta http-equiv="refresh" content="4;url=/dashboard">
</head>
<body class="min-h-screen bg-gray-50 flex items-center justify-center p-4">
    <div class="text-center max-w-sm">
        <div class="text-8xl font-bold mb-4" style="color:#d1e4f1;">404</div>
        <h1 class="text-xl font-bold text-gray-800 mb-2">الصفحة غير موجودة</h1>
        <p class="text-gray-500 mb-2 text-sm">الصفحة التي تبحث عنها غير موجودة أو تم حذفها</p>
        <p class="text-sm mb-6" style="color:#0F4C75;">سيتم تحويلك للرئيسية تلقائياً خلال ثوانٍ...</p>

        <!-- شريط التقدم -->
        <div class="w-48 mx-auto h-1 bg-gray-200 rounded-full mb-6 overflow-hidden">
            <div id="progress-bar" class="h-full rounded-full transition-all" style="background:#0F4C75; width:100%; animation: shrink 4s linear forwards;"></div>
        </div>

        <a href="/dashboard" class="inline-block text-white px-8 py-2.5 rounded-lg text-sm font-semibold transition" style="background:#0F4C75;">
            العودة للرئيسية الآن
        </a>
    </div>

    <style>
        @keyframes shrink {
            from { width: 100%; }
            to   { width: 0%; }
        }
    </style>
</body>
</html>
