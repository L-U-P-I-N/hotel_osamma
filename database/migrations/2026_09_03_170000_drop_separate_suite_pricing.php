<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * تسعيرة واحدة للجناح: القسم غرفة، والجناح غرفتان.
 *
 * وجود تسعيرة ثانية مستقلة للجناح (نطاق على النوع + سعر يدوي على الغرفة)
 * كان يسمح بأن يختلف سعر الجناح عن مجموع قسميه دون أثر ظاهر — وهو منفذ
 * التلاعب. تُزال التسعيرة الثانية ويُشتق سعر الجناح من القسمين دائماً.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('room_types', 'suite_min_price')) {
            Schema::table('room_types', function (Blueprint $table) {
                $table->dropColumn(['suite_min_price', 'suite_max_price']);
            });
        }

        // العمود يبقى في القاعدة للسجل التاريخي لكنه لم يعد يُقرأ؛ نُفرغه حتى
        // لا يظنّ أحد أن رقماً قديماً فيه ما زال مؤثراً على التسعير.
        if (Schema::hasColumn('rooms', 'suite_price_yer')) {
            DB::table('rooms')->whereNotNull('suite_price_yer')->update(['suite_price_yer' => null]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('room_types', 'suite_min_price')) {
            Schema::table('room_types', function (Blueprint $table) {
                $table->decimal('suite_min_price', 10, 2)->default(0)->after('max_price');
                $table->decimal('suite_max_price', 10, 2)->default(0)->after('suite_min_price');
            });
        }
    }
};
