<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>رمز التحقق - فندق أسامة</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config={theme:{extend:{fontFamily:{cairo:['Cairo','sans-serif']},colors:{primary:{DEFAULT:'#0F4C75',800:'#0F4C75',700:'#1e578f',200:'#c5d8ea'}}}}}</script>
    <style>body{font-family:'Cairo',sans-serif;}</style>
</head>
<body class="min-h-screen bg-gradient-to-br from-primary-800 to-primary-900 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-green-400 rounded-2xl mb-4 shadow-lg">
                <svg class="w-10 h-10 text-white" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                </svg>
            </div>
            <h1 class="text-white text-2xl font-bold">فندق أسامة</h1>
            <p class="text-primary-200 text-sm mt-1">أدخل رمز التحقق</p>
        </div>

        <div class="bg-white rounded-2xl shadow-2xl p-8">
            <h2 class="text-xl font-semibold text-gray-800 mb-2 text-center">تعيين كلمة مرور جديدة</h2>
            <p class="text-gray-500 text-sm text-center mb-6">أدخل الرمز الذي وصلك عبر WhatsApp ثم كلمة المرور الجديدة</p>

            @if($errors->any())
            <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
            @endif

            <form method="POST" action="{{ route('password.reset') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="username" value="{{ $username }}">

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">رمز التحقق (6 أرقام)</label>
                    <input type="text" name="otp" maxlength="6" inputmode="numeric" autofocus
                           value="{{ old('otp') }}"
                           class="w-full px-4 py-3 border @error('otp') border-red-400 bg-red-50 @else border-gray-300 @enderror rounded-xl focus:ring-2 focus:ring-primary-500 outline-none transition text-center text-2xl font-bold tracking-widest"
                           placeholder="• • • • • •">
                    @error('otp')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">كلمة المرور الجديدة</label>
                    <div class="relative">
                        <input type="password" name="password" id="password" minlength="6"
                               class="w-full px-4 py-3 border @error('password') border-red-400 bg-red-50 @else border-gray-300 @enderror rounded-xl focus:ring-2 focus:ring-primary-500 outline-none transition text-sm"
                               placeholder="6 أحرف على الأقل">
                        <button type="button" onclick="togglePass('password','eye1','eye1c')" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                            <svg id="eye1" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            <svg id="eye1c" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                        </button>
                    </div>
                    @error('password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">تأكيد كلمة المرور</label>
                    <div class="relative">
                        <input type="password" name="password_confirmation" id="password2"
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 outline-none transition text-sm"
                               placeholder="أعد كتابة كلمة المرور">
                        <button type="button" onclick="togglePass('password2','eye2','eye2c')" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                            <svg id="eye2" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            <svg id="eye2c" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                        </button>
                    </div>
                </div>

                <button type="submit"
                        class="w-full bg-primary-800 text-white py-3 rounded-xl font-semibold hover:bg-primary-700 transition text-sm mt-2">
                    تعيين كلمة المرور الجديدة
                </button>
            </form>
        </div>

        <div class="text-center mt-6">
            <a href="{{ route('password.request') }}" class="text-primary-200 text-sm hover:text-white transition">
                ← طلب رمز جديد
            </a>
        </div>
    </div>

    <script>
    function togglePass(inputId, eyeId, eyeClosedId) {
        const input = document.getElementById(inputId);
        const eye = document.getElementById(eyeId);
        const eyeClosed = document.getElementById(eyeClosedId);
        if (input.type === 'password') {
            input.type = 'text';
            eye.classList.add('hidden');
            eyeClosed.classList.remove('hidden');
        } else {
            input.type = 'password';
            eye.classList.remove('hidden');
            eyeClosed.classList.add('hidden');
        }
    }
    </script>
</body>
</html>
