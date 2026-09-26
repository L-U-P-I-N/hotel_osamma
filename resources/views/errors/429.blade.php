<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head><meta charset="UTF-8"><title>429 - محاولات كثيرة</title>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<style>body{font-family:'Cairo',sans-serif;}</style>
</head>
<body class="min-h-screen bg-gray-50 flex items-center justify-center p-4">
    <div class="text-center max-w-md">
        <div class="text-9xl font-bold text-orange-200 mb-4">429</div>
        <h1 class="text-2xl font-bold text-gray-800 mb-2">محاولات كثيرة</h1>
        <p class="text-gray-500 mb-6">{{ $friendly ?? 'أرسلت طلبات كثيرة في وقت قصير. انتظر قليلاً ثم أعد المحاولة.' }}</p>
        <a href="{{ url('/dashboard') }}" class="text-white px-6 py-2 rounded-lg transition inline-block" style="background:#0F4C75">العودة للرئيسية</a>
    </div>
</body>
</html>
