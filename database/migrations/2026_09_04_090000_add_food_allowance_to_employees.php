<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * صرفية الطعام والشراب مبلغ شهري مستقل عن الراتب: يتجدد كل شهر، ويُصرف
 * يومياً خلال الشهر، ولا يُخصم من الراتب الأساسي الذي يُستلم في نهايته.
 * كان كل ما يُصرف للموظف يُخصم من راتبه، فيختلط المصروف اليومي بالراتب.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->decimal('food_allowance', 10, 2)->default(0)->after('base_salary');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('food_allowance');
        });
    }
};
