@extends('layouts.app')
@section('title', $template->exists ? 'تعديل قالب' : 'قالب جديد')
@section('page-title', $template->exists ? 'تعديل قالب التصدير' : 'قالب تصدير جديد')

@php
    // حدّا العنصر النائب كمتغيّرين: Blade لا يقبل {{ }} متداخلة داخل تعبير
    $open  = '{{ ';
    $close = ' }}';
@endphp

@section('content')
<form method="POST"
      action="{{ $template->exists ? route('pdf-templates.update', $template) : route('pdf-templates.store') }}"
      class="space-y-5">
    @csrf
    @if($template->exists) @method('PUT') @endif

    @if($errors->any())
    <div class="rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
        @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
    </div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5 grid md:grid-cols-4 gap-4">
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1.5">اسم القالب *</label>
            <input type="text" name="name" required maxlength="120" value="{{ old('name', $template->name) }}"
                   placeholder="مثال: فاتورة بتصميم مختصر"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-300">
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1.5">التقرير *</label>
            <select name="report_key" id="reportKey" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none bg-white">
                @foreach($reports as $key => $report)
                <option value="{{ $key }}" @selected(old('report_key', $template->report_key) === $key)>{{ $report['label'] }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1.5">عنوان التصدير</label>
            <input type="text" name="doc_title" id="docTitle" maxlength="150" value="{{ old('doc_title', $template->doc_title) }}"
                   placeholder="يظهر في ترويسة المستند"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-300">
        </div>
        <div class="flex items-center gap-5 md:items-end">
            <label class="flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" name="is_default" value="1" @checked(old('is_default', $template->is_default)) class="rounded">
                الافتراضي
            </label>
            <label class="flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $template->is_active ?? true)) class="rounded">
                مفعّل
            </label>
        </div>
    </div>

    <div class="grid lg:grid-cols-5 gap-5">
        {{-- العناصر المتاحة: المدير لا يحتاج حفظ أسماء، ينقر فيُدرَج --}}
        <div class="lg:col-span-1 bg-white rounded-2xl shadow-sm border border-gray-200 p-4 h-fit">
            <h3 class="font-bold text-gray-800 text-sm mb-1">العناصر المتاحة</h3>
            <p class="text-[11px] text-gray-400 mb-3">انقر العنصر لإدراجه في الكود</p>

            <div class="space-y-1 mb-4">
                @foreach($placeholders['مفردة'] as $name)
                <button type="button" data-insert="{{ $open . $name . $close }}" onclick="insertAtCursor(this.dataset.insert)"
                        class="w-full text-right px-2 py-1.5 rounded text-[11px] bg-gray-50 hover:bg-blue-50 text-gray-700">
                    {{ $name }}
                </button>
                @endforeach
            </div>

            @foreach($placeholders['جداول'] as $table => $columns)
            <div class="border-t border-gray-100 pt-3">
                <p class="text-[11px] font-bold text-gray-700 mb-1.5">جدول: {{ $table }}</p>
                <button type="button" onclick="insertLoop('{{ $table }}')"
                        class="w-full text-right px-2 py-1 rounded text-[11px] bg-emerald-50 hover:bg-emerald-100 text-emerald-800 mb-1.5">
                    إدراج تكرار الجدول
                </button>
                @foreach($columns as $column)
                <button type="button" data-insert="{{ $open . $column . $close }}" onclick="insertAtCursor(this.dataset.insert)"
                        class="w-full text-right px-2 py-1.5 rounded text-[11px] bg-gray-50 hover:bg-blue-50 text-gray-600">
                    {{ $column }}
                </button>
                @endforeach
            </div>
            @endforeach

            <div class="border-t border-gray-100 pt-3 mt-3 text-[11px] text-gray-500 space-y-1">
                <p class="font-bold text-gray-700">مرشِّحات:</p>
                <p><code class="bg-gray-100 px-1">| رقم</code> فواصل الآلاف</p>
                <p><code class="bg-gray-100 px-1">| مبلغ</code> بمنزلتين عشريتين</p>
                <p><code class="bg-gray-100 px-1">| تاريخ</code> يوم/شهر/سنة</p>
            </div>
        </div>

        <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-4 py-2.5 bg-gray-50 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-bold text-gray-800 text-sm">كود القالب</h3>
                <button type="button" onclick="runPreview()"
                        class="px-3 py-1.5 rounded-lg text-white text-xs font-bold" style="background:#0F4C75">
                    معاينة
                </button>
            </div>
            <textarea name="body" id="templateBody" required spellcheck="false"
                      class="w-full h-[320px] lg:h-[520px] p-4 text-xs outline-none resize-none"
                      style="font-family: ui-monospace, SFMono-Regular, Menlo, 'Cairo', monospace;"
                      dir="ltr">{{ old('body', $template->body) }}</textarea>
        </div>

        <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-4 py-2.5 bg-gray-50 border-b border-gray-100">
                <h3 class="font-bold text-gray-800 text-sm">المعاينة</h3>
            </div>
            <div id="previewWarning" class="hidden px-4 py-2 bg-amber-50 border-b border-amber-100 text-[11px] text-amber-800"></div>
            <div id="previewError" class="hidden px-4 py-3 bg-red-50 text-xs text-red-700"></div>
            <iframe id="previewFrame" class="w-full h-[320px] lg:h-[520px] bg-white"></iframe>
        </div>
    </div>

    <div class="flex gap-3">
        <button type="submit" class="px-6 py-3 rounded-xl text-white text-sm font-bold" style="background:#0F4C75">
            حفظ القالب
        </button>
        <a href="{{ route('pdf-templates.index') }}"
           class="px-6 py-3 rounded-xl border border-gray-300 text-gray-600 text-sm hover:bg-gray-50">إلغاء</a>
    </div>
</form>

@push('scripts')
<script>
(function () {
    const body    = document.getElementById('templateBody');
    const frame   = document.getElementById('previewFrame');
    const warning = document.getElementById('previewWarning');
    const errorBox= document.getElementById('previewError');

    window.insertAtCursor = function (text) {
        const start = body.selectionStart, end = body.selectionEnd;
        body.value = body.value.slice(0, start) + text + body.value.slice(end);
        body.focus();
        body.selectionStart = body.selectionEnd = start + text.length;
    };

    window.insertLoop = function (table) {
        insertAtCursor('\n{% تكرار ' + table + ' %}\n    \n{% نهاية %}\n');
    };

    window.runPreview = async function () {
        errorBox.classList.add('hidden');
        warning.classList.add('hidden');

        try {
            const response = await fetch('{{ route('pdf-templates.preview') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({
                    report_key: document.getElementById('reportKey').value,
                    doc_title:  document.getElementById('docTitle').value,
                    body:       body.value,
                }),
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                errorBox.textContent = data.message || 'تعذّرت المعاينة.';
                errorBox.classList.remove('hidden');
                return;
            }

            frame.srcdoc = data.html;

            if (data.warning) {
                warning.textContent = data.warning;
                warning.classList.remove('hidden');
            }
        } catch (e) {
            errorBox.textContent = 'تعذّر الاتصال بالخادم للمعاينة.';
            errorBox.classList.remove('hidden');
        }
    };

    // معاينة أولى تلقائياً كي يرى المدير النتيجة فور فتح الصفحة
    if (body.value.trim()) runPreview();
})();
</script>
@endpush
@endsection
