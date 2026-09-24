@props([
    // الجهة التي توضع فيها أيقونة العين: الافتراضي يمين الحقل لأن حقول كلمات
    // المرور تُكتب بالإنجليزية (dir="ltr") فتبدأ حروفها من اليسار.
    'align' => 'right',
])

@php
    // معرّف فريد لكل حقل: الصفحة قد تحوي أكثر من حقل كلمة مرور (جديدة،
    // تأكيدها، الحالية) فلا يصلح معرّف ثابت واحد.
    $__pwId = 'pw_' . \Illuminate\Support\Str::random(8);
    // الحشو بنمط مضمَّن لا بصنف: يضمن ألا ينزلق النص تحت الأيقونة مهما كانت
    // أصناف الحشو التي يمرّرها النموذج المستدعي.
    $__pad  = $align === 'left' ? 'padding-left:2.4rem;' : 'padding-right:2.4rem;';
@endphp

<div class="relative">
    <input {{ $attributes->merge(['type' => 'password', 'id' => $__pwId]) }} style="{{ $__pad }}">

    <button type="button"
            data-pw-toggle="{{ $__pwId }}"
            aria-label="إظهار أو إخفاء كلمة المرور"
            title="إظهار / إخفاء كلمة المرور"
            class="absolute {{ $align === 'left' ? 'left-2.5' : 'right-2.5' }} top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition p-0.5">
        <svg data-pw-eye class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
        </svg>
        <svg data-pw-eye-off class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
        </svg>
    </button>
</div>

@once
<script>
// مستمع واحد مفوَّض لكل حقول كلمات المرور في الصفحة — يعمل بلا Alpine كي
// يصلح في شاشات الدخول المستقلة أيضاً، ومع الحقول المضافة لاحقاً للصفحة.
document.addEventListener('click', function (event) {
    const button = event.target.closest('[data-pw-toggle]');
    if (!button) return;

    const input = document.getElementById(button.dataset.pwToggle);
    if (!input) return;

    const hidden = input.type === 'password';
    input.type = hidden ? 'text' : 'password';

    button.querySelector('[data-pw-eye]').classList.toggle('hidden', hidden);
    button.querySelector('[data-pw-eye-off]').classList.toggle('hidden', !hidden);

    // إعادة التركيز والمؤشر لآخر النص حتى يُكمل المستخدم الكتابة مباشرةً
    input.focus();
    const end = input.value.length;
    try { input.setSelectionRange(end, end); } catch (e) {}
});
</script>
@endonce
