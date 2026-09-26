<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 18mm 12mm; }
        body { font-family: 'noto naskh arabic', sans-serif; direction: rtl; font-size: 11px; color: #1f2937; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 5px 6px; border: 1px solid #d1d5db; text-align: right; }
        th { background: #f3f4f6; font-weight: 700; }
        h1, h2, h3 { margin: 0 0 8px; }
    </style>
</head>
<body>
    {{-- نفس ترويسة بقية التصديرات: القالب يغيّر المتن لا هوية المستند --}}
    @include('partials.pdf-hotel-header-full', ['docTitle' => $docTitle, 'docMeta' => $docMeta])

    {{-- ناتج قالب المدير: مُنقّى عند الحفظ ومُهرَّب عند الاستبدال --}}
    {!! $body !!}
</body>
</html>
