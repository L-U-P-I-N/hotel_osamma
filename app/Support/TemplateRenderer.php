<?php
namespace App\Support;

/**
 * محرّك قوالب التصدير: HTML مع عناصر نائبة، بلا تنفيذ أي كود.
 *
 * لماذا محرّك خاص بدل Blade؟ لأن تمرير نصّ مخزَّن في قاعدة البيانات إلى
 * Blade يعني ترجمته إلى PHP وتنفيذه — فمن يسرق كلمة مرور المدير، أو يستغل
 * ثغرة في شاشة القوالب، ينفّذ ما يشاء على خادم الفندق ويقرأ بيانات النزلاء
 * وسندات التحويل. هذا المحرّك يقرأ القالب كنصّ ويستبدل فيه، ولا يستدعي
 * eval ولا Blade::compileString ولا أي تنفيذ إطلاقاً.
 *
 * ما يفهمه القالب:
 *   {{ اسم_المتغير }}                قيمة مفردة (تُهرَّب دائماً)
 *   {{ مبلغ | رقم }}                 تنسيق رقمي بفواصل الآلاف
 *   {{ تاريخ | تاريخ }}              صيغة يوم/شهر/سنة
 *   {% تكرار الصفوف %} … {% نهاية %}  تكرار على جدول
 *   {% إذا حقل %} … {% نهاية %}       إظهار جزء عند وجود قيمة
 */
class TemplateRenderer
{
    /** الحد الأقصى للصفوف المطبوعة، حمايةً من قالب يستهلك الذاكرة. */
    private const MAX_ROWS = 5000;

    public function render(string $template, array $data): string
    {
        $html = $this->renderLoops($template, $data);
        $html = $this->renderConditionals($html, $data);

        return $this->renderPlaceholders($html, $data);
    }

    /** {% تكرار المفتاح %} … {% نهاية %} */
    private function renderLoops(string $template, array $data): string
    {
        return preg_replace_callback(
            '/\{%\s*(?:تكرار|for)\s+([A-Za-z0-9_\x{0600}-\x{06FF}]+)\s*%\}(.*?)\{%\s*(?:نهاية|end)\s*%\}/us',
            function (array $match) use ($data) {
                $rows = $data[$match[1]] ?? [];

                if ($rows instanceof \Illuminate\Support\Collection) {
                    $rows = $rows->all();
                }

                if (!is_array($rows)) {
                    return '';
                }

                $out = '';
                foreach (array_slice($rows, 0, self::MAX_ROWS) as $index => $row) {
                    $scope = is_array($row) ? $row : (array) $row;
                    $scope['ترتيب'] = $scope['index'] = $index + 1;

                    $out .= $this->renderPlaceholders(
                        $this->renderConditionals($match[2], $scope),
                        $scope
                    );
                }

                return $out;
            },
            $template
        ) ?? $template;
    }

    /** {% إذا المفتاح %} … {% نهاية %} */
    private function renderConditionals(string $template, array $data): string
    {
        return preg_replace_callback(
            '/\{%\s*(?:إذا|if)\s+([A-Za-z0-9_\x{0600}-\x{06FF}]+)\s*%\}(.*?)\{%\s*(?:نهاية|end)\s*%\}/us',
            fn (array $match) => $this->isTruthy($data[$match[1]] ?? null) ? $match[2] : '',
            $template
        ) ?? $template;
    }

    /** {{ المفتاح }} و {{ المفتاح | مُرشِّح }} */
    private function renderPlaceholders(string $template, array $data): string
    {
        return preg_replace_callback(
            '/\{\{\s*([A-Za-z0-9_\x{0600}-\x{06FF}]+)\s*(?:\|\s*([A-Za-z0-9_\x{0600}-\x{06FF}]+)\s*)?\}\}/u',
            function (array $match) use ($data) {
                $value = $data[$match[1]] ?? '';

                if (is_array($value) || is_object($value)) {
                    return '';
                }

                return e($this->applyFilter($value, $match[2] ?? null));
            },
            $template
        ) ?? $template;
    }

    private function applyFilter(mixed $value, ?string $filter): string
    {
        if ($filter === null || $value === '' || $value === null) {
            return (string) $value;
        }

        return match ($filter) {
            'رقم', 'number'   => is_numeric($value) ? number_format((float) $value, 0) : (string) $value,
            'مبلغ', 'money'   => is_numeric($value) ? number_format((float) $value, 2) : (string) $value,
            'تاريخ', 'date'   => $this->asDate($value, 'd/m/Y'),
            'وقت', 'datetime' => $this->asDate($value, 'd/m/Y H:i'),
            default           => (string) $value,
        };
    }

    private function asDate(mixed $value, string $format): string
    {
        try {
            return \Carbon\Carbon::parse((string) $value)->format($format);
        } catch (\Throwable) {
            return (string) $value;
        }
    }

    private function isTruthy(mixed $value): bool
    {
        if ($value instanceof \Illuminate\Support\Collection) {
            return $value->isNotEmpty();
        }

        if (is_array($value)) {
            return $value !== [];
        }

        return !in_array($value, [null, '', 0, '0', false], true);
    }

    /** العناصر النائبة المستعملة في قالب — لعرض تحذير بالمجهول منها. */
    public static function placeholdersIn(string $template): array
    {
        preg_match_all(
            '/\{\{\s*([A-Za-z0-9_\x{0600}-\x{06FF}]+)\s*(?:\|[^}]*)?\}\}|\{%\s*(?:تكرار|for|إذا|if)\s+([A-Za-z0-9_\x{0600}-\x{06FF}]+)\s*%\}/u',
            $template,
            $matches
        );

        return array_values(array_unique(array_filter(array_merge($matches[1], $matches[2]))));
    }
}
