<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ملاحظات عامة على الفندق (لا على غرفة أو حجز): تنبيه لموظف الوردية التالية،
 * أمر إداري، أو أي شيء يجب أن يقرأه كل من يفتح صفحة الحجوزات. تظهر في أعلى
 * الصفحة بشكل دائم حتى تُعلَّم منتهية، فلا تضيع الرسالة بين الورديات.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotel_notes', function (Blueprint $table) {
            $table->id();
            // نوع نصّي لا enum: إضافة نوع لاحقاً لا تحتاج تعديل بنية الجدول
            $table->string('type', 30)->default('general');
            $table->text('body');
            // ملاحظة مثبّتة تبقى أعلى القائمة وإن كتب غيرها ملاحظات أحدث
            $table->boolean('is_pinned')->default(false);
            // ملاحظة منتهية تبقى في السجل للمراجعة ولا تُزعج الموظف بعدها
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // الاستعلام الأكثر تكراراً: الملاحظات القائمة مرتّبة بالتثبيت
            $table->index(['resolved_at', 'is_pinned']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotel_notes');
    }
};
