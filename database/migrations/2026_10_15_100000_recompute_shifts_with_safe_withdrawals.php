<?php

use App\Models\CashWithdrawal;
use App\Models\Shift;
use App\Services\ShiftService;
use Illuminate\Database\Migrations\Migration;

/**
 * إعادة احتساب الورديات المفتوحة التي تحمل سحبيات من الصندوق العام.
 *
 * قبل فصل المسارين كان مصروف الخزنة يُربط بالوردية كأي سحب، فسجلاتٌ قائمة
 * تحمل shift_id ومصدرَ تمويلٍ «الصندوق العام» معاً. وcomputeTotals صارت تستثني
 * الصندوق العام، لكن المجاميع المحفوظة لا تتغيّر بنفسها: تبقى الوردية عاجزة
 * بمالٍ خرج من خزنة الفندق حتى يُعاد احتسابها.
 *
 * المفتوحة وحدها: المقفلة وُقِّعت وقُيّدت أرقامها (وربما خُصم عجزها من راتب)،
 * فإعادة كتابة تاريخها أسوأ من تركه موصوفاً. السحبية تبقى مربوطة بالوردية
 * ومرئية في سجلّها بعمود «مصدر التمويل» — ظاهرةٌ غير محتسبة.
 */
return new class extends Migration
{
    public function up(): void
    {
        $shiftIds = CashWithdrawal::where('funding_source', 'general_safe')
            ->whereNotNull('shift_id')
            ->distinct()
            ->pluck('shift_id');

        if ($shiftIds->isEmpty()) {
            return;
        }

        $service = app(ShiftService::class);

        foreach (Shift::whereIn('id', $shiftIds)->where('is_closed', false)->get() as $shift) {
            $service->computeTotals($shift);
        }
    }

    /** إعادة الاحتساب لا تُنقض: الأرقام مشتقّة من السجلات لا مُدخَلة. */
    public function down(): void {}
};
