<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * مفتاح تفرّد للقيود الآلية.
 *
 * كان القيد يُربَط بمصدره بـ(source_type, source_id) بفهرس عادي لا فريد وبلا
 * عمود يميّز نوع الحدث. فتعديل رسم ضرر يُنشئ قيداً ثالثاً بنفس المفتاح، ولا
 * وسيلة لإعادة تشغيل ترحيل فاشل دون تكراره. العمود event يكمل المفتاح، والفهرس
 * الفريد يجعل كل عملية ترحيل آمنة التكرار (idempotent) — وهو شرط لازم قبل
 * ترحيل البيانات التاريخية دفعةً واحدة.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->string('event', 60)->nullable()->after('source_id');
            // قيود دفعة واحدة (تدقيق ليلي، إهلاك شهري) تُجمَع بمرجع واحد
            $table->string('batch_ref', 60)->nullable()->after('event');
            $table->index('batch_ref');
        });

        // القيود القائمة قبل هذا العمود: نُسمّيها بحدث عام كي يعمل الفهرس الفريد
        DB::table('journal_entries')->whereNull('event')->update(['event' => 'legacy']);

        // الصفوف المكرّرة سابقاً (نفس المصدر مرحّلاً أكثر من مرة) تُميَّز برقمها
        // كي لا يفشل إنشاء الفهرس الفريد — ولا يُحذف منها شيء.
        $duplicates = DB::table('journal_entries')
            ->select('source_type', 'source_id', 'event')
            ->groupBy('source_type', 'source_id', 'event')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $dup) {
            $rows = DB::table('journal_entries')
                ->where('source_type', $dup->source_type)
                ->where('source_id', $dup->source_id)
                ->where('event', $dup->event)
                ->orderBy('id')
                ->pluck('id')
                ->slice(1);

            foreach ($rows as $index => $id) {
                DB::table('journal_entries')->where('id', $id)
                    ->update(['event' => 'legacy#' . ($index + 1)]);
            }
        }

        Schema::table('journal_entries', function (Blueprint $table) {
            $table->unique(['source_type', 'source_id', 'event'], 'journal_entries_source_event_unique');
        });
    }

    public function down(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropUnique('journal_entries_source_event_unique');
            $table->dropIndex(['batch_ref']);
            $table->dropColumn(['event', 'batch_ref']);
        });
    }
};
