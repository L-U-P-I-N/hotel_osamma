<?php

namespace App\Http\Requests;

use App\Models\ChartOfAccount;
use Illuminate\Foundation\Http\FormRequest;

/**
 * تعديل حساب قائم — الاسم والقسم والملاحظة وحالة الإيقاف فقط.
 *
 * ما لا يُعدَّل بعد إنشاء الحساب: الكود والنوع والأب والمستوى. تغيير أيٍّ منها
 * يُعيد كتابة تاريخ مالي مُرحَّل: الكود يكسر ارتباط سطور القيود، والنوع يقلب
 * الرصيد الطبيعي فتنعكس إشارة كل حركة سابقة، والأب ينقل المبالغ بين مراكز
 * التكلفة في كل تقرير صدر من قبل. من أراد بنية أخرى يُنشئ حساباً ويُوقف القديم.
 */
class UpdateChartOfAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('accounts.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'name_ar'    => ['required', 'string', 'max:150'],
            'name_en'    => ['nullable', 'string', 'max:150'],
            'department' => ['nullable', 'in:' . implode(',', ChartOfAccount::DEPARTMENTS)],
            'notes'      => ['nullable', 'string', 'max:500'],
            'suspended'  => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return ['name_ar.required' => 'اسم الحساب مطلوب.'];
    }
}
