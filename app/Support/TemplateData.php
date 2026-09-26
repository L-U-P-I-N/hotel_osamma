<?php
namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Collection;

/**
 * تحويل بيانات التقرير إلى عناصر نائبة يفهمها قالب المدير.
 *
 * بدل أن نُلزم المدير بمعرفة أسماء المتغيرات البرمجية، نسطّح بيانات
 * التقرير إلى قيم مفردة وجداول بأسماء واضحة، ونعرض عليه قائمتها في
 * المحرِّر. لا يصل إلى القالب كائن حيّ إطلاقاً — قيم نصية وأرقام وجداول فقط.
 */
class TemplateData
{
    /** أقصى عدد صفوف يُمرَّر لجدول واحد. */
    private const MAX_ROWS = 5000;

    /** عناصر متاحة في كل قالب مهما كان التقرير. */
    public static function common(string $docTitle = ''): array
    {
        return [
            'اسم_الفندق'    => Setting::hotelName(),
            'عنوان_التقرير' => $docTitle,
            'التاريخ'       => now()->format('d/m/Y'),
            'الوقت'         => now()->format('H:i'),
            'التاريخ_والوقت'=> now()->format('d/m/Y H:i'),
            'المستخدم'      => auth()->user()?->name ?? '',
            'هاتف_الفندق'   => (string) Setting::get('hotel_phone', ''),
            'عنوان_الفندق'  => (string) Setting::get('hotel_address_ar', ''),
            'العملة'        => (string) Setting::get('currency_symbol', 'ر.ي'),
        ];
    }

    /**
     * تسطيح بيانات عرض التقرير: القيم المفردة كما هي، والمجموعات كجداول.
     */
    public static function fromViewData(array $viewData, string $docTitle = ''): array
    {
        $flat = self::common($docTitle);

        foreach ($viewData as $key => $value) {
            if (is_scalar($value) || $value === null) {
                $flat[$key] = $value;
                continue;
            }

            if ($value instanceof \DateTimeInterface) {
                $flat[$key] = $value->format('d/m/Y');
                continue;
            }

            $rows = self::toRows($value);
            if ($rows !== null) {
                $flat[$key] = $rows;
            }
        }

        return $flat;
    }

    /** تحويل مجموعة/مصفوفة إلى صفوف مسطَّحة، أو null إن لم تكن جدولاً. */
    private static function toRows(mixed $value): ?array
    {
        if ($value instanceof Collection) {
            $value = $value->all();
        }

        if (!is_array($value)) {
            return null;
        }

        $rows = [];
        foreach (array_slice($value, 0, self::MAX_ROWS) as $item) {
            $row = match (true) {
                is_array($item)                                  => $item,
                $item instanceof \Illuminate\Database\Eloquent\Model => $item->attributesToArray(),
                is_object($item)                                 => get_object_vars($item),
                default                                          => null,
            };

            if ($row === null) {
                continue;
            }

            $rows[] = array_map(
                fn ($cell) => is_scalar($cell) || $cell === null ? $cell : '',
                $row
            );
        }

        return $rows;
    }

    /**
     * قائمة العناصر النائبة المتاحة، لعرضها في المحرِّر.
     * تعيد ['مفردة' => [...], 'جداول' => ['المفتاح' => [أعمدته]]]
     */
    public static function describe(array $flat): array
    {
        $singles = [];
        $tables  = [];

        foreach ($flat as $key => $value) {
            if (is_array($value)) {
                $tables[$key] = array_keys($value[0] ?? []);
            } else {
                $singles[] = $key;
            }
        }

        sort($singles);
        ksort($tables);

        return ['مفردة' => $singles, 'جداول' => $tables];
    }
}
