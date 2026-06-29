<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - الفندق السعودي</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config={theme:{extend:{fontFamily:{cairo:['Cairo','sans-serif']},colors:{primary:{DEFAULT:'#0F4C75',800:'#0F4C75',700:'#1e578f',600:'#2d6aab',100:'#c5d8ea'},accent:{DEFAULT:'#D4A574'}}}}}</script>
    <style>
        /* الصفحة دائماً بنمط فاتح حتى لو كان نظام المستخدم في الوضع الليلي،
           حتى لا تنقلب حقول الإدخال إلى ألوان داكنة تُخفي النص */
        html { color-scheme: light; }
        body { font-family:'Cairo',sans-serif; }
        input { background-color:#fff !important; color:#111827 !important; }
        input::placeholder { color:#9ca3af !important; }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-primary-800 to-primary-900 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <!-- Logo Card -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-accent rounded-2xl mb-4 shadow-lg">
                <span class="text-primary-900 font-bold text-4xl">س</span>
            </div>
            <h1 class="text-white text-2xl font-bold">الفندق السعودي</h1>
            <p class="text-primary-200 text-sm mt-1">نظام إدارة الفندق</p>
        </div>

        <!-- Login Card -->
        <div class="bg-white rounded-2xl shadow-2xl p-8">
            <h2 class="text-xl font-semibold text-gray-800 mb-6 text-center">تسجيل الدخول</h2>

            @if($errors->any())
            <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm">
                @foreach($errors->all() as $error)
                    <p class="flex items-start gap-2">
                        <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                        {{ $error }}
                    </p>
                @endforeach
            </div>
            @endif

            {{-- تحذير الحساب مستخدم على جهاز آخر --}}
            @if(session('show_force_login'))
            <div class="mb-4 p-4 bg-amber-50 border border-amber-200 rounded-xl">
                <div class="flex items-start gap-3 mb-3">
                    <div class="w-9 h-9 rounded-xl bg-amber-100 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-amber-800">الحساب مستخدم على جهاز آخر</p>
                        <p class="text-xs text-amber-700 mt-0.5">يمكنك إنهاء الجلسة الأخرى وتسجيل الدخول هنا</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('login.force') }}">
                    @csrf
                    <input type="hidden" name="username" value="{{ session('force_username') }}">
                    <div class="mb-3">
                        <label class="block text-xs font-medium text-amber-800 mb-1">أدخل كلمة المرور للتأكيد</label>
                        <input type="password" name="password" required autofocus
                               class="w-full px-3 py-2 border border-amber-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-400 outline-none bg-white"
                               placeholder="كلمة المرور">
                    </div>
                    <button type="submit"
                            class="w-full py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-sm font-semibold transition">
                        إنهاء الجلسة الأخرى والدخول هنا
                    </button>
                </form>
            </div>
            @endif

            <form method="POST" action="{{ route('login.post') }}" class="space-y-5" autocomplete="off">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">اسم المستخدم</label>
                    <input type="text" name="username" value="{{ old('username') }}" required autofocus
                           autocomplete="off"
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none transition text-sm"
                           placeholder="أدخل اسم المستخدم">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">كلمة المرور</label>
                    <div class="relative">
                        <input type="password" name="password" id="password" required
                               autocomplete="new-password"
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none transition text-sm"
                               placeholder="أدخل كلمة المرور">
                        <button type="button" onclick="togglePassword()"
                                class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition">
                            <svg id="eye-open" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg id="eye-closed" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                            </svg>
                        </button>
                    </div>
                </div>
                <button type="submit"
                        class="w-full bg-primary-800 text-white py-3 rounded-xl font-semibold hover:bg-primary-700 transition-colors duration-200 text-sm">
                    دخول
                </button>
                <div class="text-center">
                    <a href="{{ route('password.request') }}" class="text-sm text-primary-700 hover:underline">
                        نسيت كلمة المرور؟
                    </a>
                </div>
            </form>
        </div>

        <p class="text-center text-primary-300 text-xs mt-6">© {{ date('Y') }} الفندق السعودي - جميع الحقوق محفوظة</p>
    </div>
<script>
function togglePassword() {
    const input = document.getElementById('password');
    const eyeOpen = document.getElementById('eye-open');
    const eyeClosed = document.getElementById('eye-closed');
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
