<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * فصل رسوم المشتريات (بقالة/خدمات النزيل) عن صندوق الفندق وتقاريره.
 *
 * - in_hotel_total: هل يُحتسب هذا الرسم ضمن إجمالي الحجز (صندوق الفندق)؟
 *   الأضرار والرسوم القديمة = true (لا نعيد كتابة التاريخ). المشتريات الجديدة = false
 *   فتُتابَع كدَين مستقل (صندوق البقالة) لا يدخل الإجمالي ولا التقارير.
 * - settled_at / settled_by: توثيق تحصيل دين المشتريات (تسليمه للبقالة) دون أي
 *   قيد محاسبي في النظام.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('extra_charges', function (Blueprint $table) {
            // الافتراضي true: أي رسم موجود مسبقاً يبقى ضمن إجمالي الفندق كما كان.
            $table->boolean('in_hotel_total')->default(true)->after('amount');
            $table->timestamp('settled_at')->nullable()->after('in_hotel_total');
            $table->foreignId('settled_by')->nullable()->after('settled_at')
                  ->constrained('users')->nullOnDelete();
        });

        // الرسوم غير الأضرار الموجودة مسبقاً: كانت ضمن الإجمالي وحُصِّلت مع الإقامة،
        // فنعتبرها مُحصَّلة حتى لا تظهر كدَين مشتريات جديد غير محصّل.
        DB::table('extra_charges')
            ->whereNull('deleted_at')
            ->where('type', '!=', 'damage')
            ->whereNull('settled_at')
            ->update(['settled_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('extra_charges', function (Blueprint $table) {
            $table->dropConstrainedForeignId('settled_by');
            $table->dropColumn(['in_hotel_total', 'settled_at']);
        });
    }
};
