<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ملاحظات فورية على الحجز: التزامات على النزيل قبل المغادرة، صيانة مطلوبة،
 * رسوم معلّقة، أو تنبيه عام — تُقرأ وتُكتب من جدول الحجوزات مباشرةً دون فتح
 * صفحة التفاصيل، فيراها كل موظف عند أول نظرة.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservation_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            // نوع نصّي لا enum: إضافة نوع جديد لاحقاً لا تحتاج تعديل بنية الجدول
            $table->string('type', 30)->default('general');
            $table->text('body');
            // ملاحظة محلولة تبقى في السجل للمراجعة ولا تُزعج الموظف بعد ذلك
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // الاستعلام الأكثر تكراراً: ملاحظات حجزٍ غير المحلولة
            $table->index(['reservation_id', 'resolved_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_notes');
    }
};
