<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * يربط الدفعات التي سُجّلت معاً في "دفعة موحدة لعدة حجوزات" برقم مجموعة واحد
     * (مثل GRP-20260716-ABCD) — فيدفع شخص واحد المبلغ الإجمالي لعدة حجوزات (أب
     * يدفع لأبنائه، أو نزيل حجز عدة غرف باسمه) ويُوزَّع المبلغ على كل حجز، مع بقاء
     * الدفعات مترابطةً للتتبّع والتقارير. قيمة null تعني دفعة فردية عادية.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('group_id')->nullable()->after('reservation_id')->index();
            // اسم من دفع فعلاً (قد يختلف عن صاحب الحجز: الأب/الشركة/الوكيل)
            $table->string('paid_by_name')->nullable()->after('group_id');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['group_id']);
            $table->dropColumn(['group_id', 'paid_by_name']);
        });
    }
};
