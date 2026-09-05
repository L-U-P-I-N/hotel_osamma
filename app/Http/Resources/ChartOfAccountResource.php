<?php

namespace App\Http\Resources;

use App\Models\ChartOfAccount;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * تمثيل JSON لحساب واحد مع أبنائه متداخلين.
 * Nested JSON representation of one account and its descendants.
 *
 * @mixin ChartOfAccount
 */
class ChartOfAccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'code'        => $this->code,
            'parent_code' => $this->parent_code,

            'name' => [
                'en' => $this->name_en,
                'ar' => $this->name_ar,
            ],

            'type'    => $this->type,
            'subtype' => $this->subtype,

            'department' => $this->when($this->department !== null, fn () => [
                'key'   => $this->department,
                'label' => [
                    'en' => $this->department,
                    'ar' => $this->department_label_ar,
                ],
            ]),

            'normal_balance' => $this->normal_balance,
            'is_posting'     => $this->is_posting,
            'is_active'      => $this->is_active,
            'level'          => $this->level,

            'currency' => config('hotel.base_currency'),

            // تُحمَّل عبر with('childrenRecursive') فلا استعلام إضافي لكل عقدة
            'children' => ChartOfAccountResource::collection(
                $this->whenLoaded('childrenRecursive')
            ),
        ];
    }
}
