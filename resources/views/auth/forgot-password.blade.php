<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>استعادة كلمة المرور - فندق أسامة</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config={theme:{extend:{fontFamily:{cairo:['Cairo','sans-serif']},colors:{primary:{DEFAULT:'#0F4C75',800:'#0F4C75',700:'#1e578f',600:'#2d6aab',100:'#c5d8ea'},accent:{DEFAULT:'#D4A574'}}}}}</script>
    <style>body{font-family:'Cairo',sans-serif;}</style>
</head>
<body class="min-h-screen bg-gradient-to-br from-primary-800 to-primary-900 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-accent rounded-2xl mb-4 shadow-lg">
                <span class="text-primary-900 font-bold text-4xl">ف</span>
            </div>
            <h1 class="text-white text-2xl font-bold">فندق أسامة</h1>
            <p class="text-primary-200 text-sm mt-1">نظام إدارة الفندق</p>
        </div>

        <div class="bg-white rounded-2xl shadow-2xl p-8">
            <h2 class="text-xl font-semibold text-gray-800 mb-2 text-center">استعادة كلمة المرور</h2>
            <p class="text-sm text-gray-500 text-center mb-6">أدخل اسم المستخدم ورمز الاسترداد لتغيير كلمة المرور</p>

            @if($errors->any())
            <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
            @endif

            <form method="POST" action="{{ route('password.reset-backup') }}" class="space-y-5">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">اسم المستخدم</label>
                    <input type="text" name="username" value="{{ old('username') }}" required autofocus
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none transition text-sm"
                           placeholder="أدخل اسم المستخدم">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">رمز الاسترداد</label>
                    <input type="text" name="backup_code" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none transition text-sm font-mono tracking-widest"
                           placeholder="XXXX-XXXX-XXXX">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">كلمة المرور الجديدة</label>
                    <div class="relative">
                        <input type="password" name="password" id="password" required minlength="6"
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none transition text-sm"
                               placeholder="أدخل كلمة المرور الجديدة">
                        <button type="button" onclick="togglePassword('password','eye-open-1','eye-closed-1')"
                                class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition">
                            <svg id="eye-open-1" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg id="eye-closed-1" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                            </svg>
                        </button>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">تأكيد كلمة المرور</label>
                    <div class="relative">
                        <input type="password" name="password_confirmation" id="password_confirmation" required minlength="6"
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none transition text-sm"
                               placeholder="أعد إدخال كلمة المرور">
                        <button type="button" onclick="togglePassword('password_confirmation','eye-open-2','eye-closed-2')"
                                class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition">
                            <svg id="eye-open-2" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg id="eye-closed-2" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                            </svg>
                        </button>
                    </div>
                </div>
                <button type="submit"
                        class="w-full bg-primary-800 text-white py-3 rounded-xl font-semibold hover:bg-primary-700 transition-colors duration-200 text-sm">
                    تغيير كلمة المرور
                </button>
                <div class="text-center">
                    <a href="{{ route('login') }}" class="text-sm text-primary-700 hover:underline">
                        العودة إلى تسجيل الدخول
                    </a>
                </div>
            </form>
        </div>

        <p class="text-center text-primary-300 text-xs mt-6">© {{ date('Y') }} فندق أسامة - جميع الحقوق محفوظة</p>
    </div>
<script>
function togglePassword(fieldId, openId, closedId) {
    const input = document.getElementById(fieldId);
    const eyeOpen = document.getElementById(openId);
    const eyeClosed = document.getElementById(closedId);
    if (input.type === 'password') {
        input.type = 'text';
        eyeOpen.classList.add('hidden');
        eyeClosed.classList.remove('hidden');
    } else {
        input.type = 'password';
        eyeOpen.classList.remove('hidden');
        eyeClosed.classList.add('hidden');
    }
}
</script>
</body>
</html>
