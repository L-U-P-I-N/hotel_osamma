<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * توسيع أنواع الهوية: رخصة قيادة، شهادة ميلاد، بطاقة عسكرية.
 *
 * العمود enum في القاعدة، فلا يكفي توسيع القائمة في الشيفرة: MySQL يرفض أي
 * قيمة خارج تعدادها، فكان الموظف يختار النوع الجديد فيسقط الحفظ.
 *
 * القائمة تُقرأ من Guest::ID_TYPES لا تُكتب هنا مرة ثانية — فإضافة نوعٍ
 * لاحقاً تحتاج ترحيلةً جديدة لكن لا تحتاج قائمةً جديدة.
 */
return new class extends Migration
{
    /** @return array<int,string> */
    private function types(): array
    {
        return array_keys(\App\Models\Guest::ID_TYPES);
    }

    public function up(): void
    {
        $this->applyEnum($this->types());
    }

    public function down(): void
    {
        // العودة للثلاثة الأصلية: ما كان على نوعٍ جديد يُردّ إلى الهوية الوطنية
        // وإلا رفضت القاعدة التضييق وسقط التراجع
        $original = ['passport', 'national_id', 'residence'];

        foreach (['guests', 'companions'] as $table) {
            DB::table($table)->whereNotIn('id_type', $original)->update(['id_type' => 'national_id']);
        }

        $this->applyEnum($original);
    }

    /** @param array<int,string> $types */
    private function applyEnum(array $types): void
    {
        // sqlite لا يعرف enum أصلاً (يخزّنها نصاً) فلا شيء ليُعدَّل
        if (!in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        $list = implode(',', array_map(fn (string $t) => "'" . $t . "'", $types));

        foreach (['guests', 'companions'] as $table) {
            if (Schema::hasColumn($table, 'id_type')) {
                DB::statement(
                    "ALTER TABLE {$table} MODIFY COLUMN id_type ENUM({$list}) NOT NULL DEFAULT 'national_id'"
                );
            }
        }
    }
};
