<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * فصل المقبوض نقداً عمّا قُبض بنكياً أو بالشبكة في أرقام الوردية.
 *
 * كان "إجمالي المقبوض" يجمع كل الدفعات بلا تمييز، والمتوقَّع في الدرج يُحسب منه
 * مطروحاً منه السحبيات والاسترجاعات. فكل تحويل بنكي يرفع المتوقَّع في الدرج بمبلغ
 * لم يدخله أصلاً، ويظهر الموظف عاجزاً عند الإقفال بمقدار ما حُوِّل — وهو عجزٌ
 * ورقيّ لا وجود له.
 *
 * العمودان الجديدان يفصلان الرقمين، ويُملآن للورديات القائمة من دفعاتها.
 * أرقام العجز/الزيادة المحفوظة لورديات أُقفلت سابقاً لا تُمَس: هي سجل تاريخي
 * لما وقّع عليه الموظف، ويُعاد حسابها فقط إن أُعيد فتح الوردية.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->decimal('total_received_cash_yer', 12, 2)->default(0)->after('total_received_usd');
            $table->decimal('total_received_noncash_yer', 12, 2)->default(0)->after('total_received_cash_yer');
        });

        // النقدي = ما دخل درجاً أو خزنة؛ وما قبل ربط الأوعية يُحدَّد بالطريقة
        $cash = DB::table('payments')
            ->select('shift_id', DB::raw('SUM(amount) as total'))
            ->where('currency', 'YER')
            ->whereNotNull('shift_id')
            ->where('method', 'cash')
            ->groupBy('shift_id')->pluck('total', 'shift_id');

        $nonCash = DB::table('payments')
            ->select('shift_id', DB::raw('SUM(amount) as total'))
            ->where('currency', 'YER')
            ->whereNotNull('shift_id')
            ->whereIn('method', ['bank_transfer', 'pos'])
            ->groupBy('shift_id')->pluck('total', 'shift_id');

        foreach ($cash->keys()->merge($nonCash->keys())->unique() as $shiftId) {
            DB::table('shifts')->where('id', $shiftId)->update([
                'total_received_cash_yer'    => $cash[$shiftId] ?? 0,
                'total_received_noncash_yer' => $nonCash[$shiftId] ?? 0,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropColumn(['total_received_cash_yer', 'total_received_noncash_yer']);
        });
    }
};
