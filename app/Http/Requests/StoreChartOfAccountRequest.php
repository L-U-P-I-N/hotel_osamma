<?php

namespace App\Http\Requests;

use App\Models\ChartOfAccount;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * إضافة حساب جديد إلى الدليل.
 *
 * القواعد هنا مرآةٌ لقيود CHECK على القاعدة (المستوى ١..٤، الترحيل للأوراق
 * وحدها، الرصيد الطبيعي يتبع النوع)، لأن اصطدام المستخدم بخطأ SQL خام لا
 * يُفهم منه شيء. والنوع والمستوى والرصيد لا تُؤخذ من النموذج أصلاً بل تُشتق
 * من الأب: حسابٌ فرعي من طبيعة أبيه، وإلا انهار معنى التجميع في التقارير.
 */
class StoreChartOfAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('accounts.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'parent_code' => ['required', 'string', 'exists:chart_of_accounts,code'],
            'code'        => ['required', 'string', 'regex:/^\d{4}$/', 'unique:chart_of_accounts,code'],
            'name_ar'     => ['required', 'string', 'max:150'],
            'name_en'     => ['nullable', 'string', 'max:150'],
            'department'  => ['nullable', 'in:' . implode(',', ChartOfAccount::DEPARTMENTS)],
            'notes'       => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'parent_code.required' => 'اختر الحساب الأب الذي يندرج تحته.',
            'parent_code.exists'   => 'الحساب الأب غير موجود في الدليل.',
            'code.required'        => 'رقم الحساب مطلوب.',
            'code.regex'           => 'رقم الحساب أربع خانات رقمية (مثال: 1130).',
            'code.unique'          => 'رقم الحساب مستعمل سلفاً.',
            'name_ar.required'     => 'اسم الحساب مطلوب.',
        ];
    }

    /** قواعد لا تُعبَّر بالسلاسل: موضع الكود في الشجرة وصلاحية الأب */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $parent = ChartOfAccount::where('code', $this->input('parent_code'))->first();

            if ($parent === null) {
                return; // رسالة exists تكفي
            }

            if ($parent->level >= 4) {
                $validator->errors()->add(
                    'parent_code',
                    'الشجرة أربعة مستويات، والحساب المختار في آخرها فلا يقبل فروعاً.'
                );

                return;
            }

            // أبٌ رُحِّل عليه قيد لا يصير تجميعياً: رصيده القديم سيُجمع مع
            // فروعه فيُحتسب مرتين في كل تقرير سابق
            if ($parent->hasJournalLines()) {
                $validator->errors()->add(
                    'parent_code',
                    'رُحِّلت قيود على «' . $parent->name_ar . '» فلا يصلح أباً — اختر حساباً تجميعياً.'
                );

                return;
            }

            $this->assertCodeFitsUnder($validator, $parent, (string) $this->input('code'));
        }];
    }

    /**
     * الكود يجب أن يقع فعلاً تحت الأب: يشاركه خاناته الأولى، ويختلف عنه في
     * خانة مستواه وحدها، وما بعدها أصفار محجوزة لفروع الحساب الجديد.
     */
    private function assertCodeFitsUnder(Validator $validator, ChartOfAccount $parent, string $code): void
    {
        if (!preg_match('/^\d{4}$/', $code)) {
            return; // رسالة regex تكفي
        }

        $level  = $parent->level;
        $prefix = substr($parent->code, 0, $level);

        if (substr($code, 0, $level) !== $prefix) {
            $validator->errors()->add(
                'code',
                'رقم الحساب يجب أن يبدأ بـ«' . $prefix . '» ليقع تحت ' . $parent->code . '.'
            );

            return;
        }

        if ($code[$level] === '0') {
            $validator->errors()->add('code', 'الخانة رقم ' . ($level + 1) . ' لا تكون صفراً في الحساب الفرعي.');

            return;
        }

        if (substr($code, $level + 1) !== str_repeat('0', 3 - $level)) {
            $validator->errors()->add(
                'code',
                'الخانات بعد موضع الحساب تبقى أصفاراً — وهي محجوزة لفروعه.'
            );
        }
    }
}
