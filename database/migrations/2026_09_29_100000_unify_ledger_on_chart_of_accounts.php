<?php

use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * المرحلة صفر: توحيد شجرة الحسابات.
 *
 * كان النظام يحمل شجرتين: جدول accounts الصغير (22 حساباً) الذي تُرحَّل إليه
 * القيود فعلاً، وجدول chart_of_accounts بشجرة USALI الكاملة (~200 حساب) الذي
 * يُعرَض ولا يُرحَّل إليه شيء. والأكواد بين الجدولين متصادمة المعاني (5100 صيانة
 * هنا ومصروفات قسم الغرف هناك، و2100 رواتب مستحقة هنا وذمم دائنة هناك)، فأي
 * تقرير يُبنى على القيود كان سيقرأ أرقاماً في غير مواضعها.
 *
 * هذه الترحيلة تجعل chart_of_accounts دفتر الأستاذ الوحيد:
 *   1. تضيف عمود account_code إلى journal_lines.
 *   2. تُعيد نسبة كل سطر مرحَّل إلى كوده الجديد — **بمعرّف الحساب القديم لا بكوده**،
 *      لأن الكود نفسه يعني شيئين مختلفين في الشجرتين.
 *   3. تتحقق أن مجموع المدين والدائن لم يتغيّر بعد النقل، وتُسقط الترحيلة عند أي فرق.
 *   4. تُسقط account_id.
 *
 * جدول accounts يبقى قائماً (مجمَّداً بلا استعمال) كي يظل التراجع ممكناً؛ يُحذف
 * في ترحيلة تنظيف لاحقة بعد استقرار التوحيد.
 *
 * ── قابلة للاستئناف ──
 * MySQL يُثبّت تغييرات البنية فور تنفيذها ولا يتراجع عنها مع المعاملة، فتوقّفُ
 * الترحيلة في منتصفها يترك العمود مضافاً بينما لا تُسجَّل الترحيلة كمنفَّذة —
 * وكل إعادة محاولة كانت تصطدم بـ«العمود موجود سلفاً». لذا كل خطوة هنا تفحص
 * حالتها قبل تنفيذها، فإعادة التشغيل تُكمل من حيث توقّفت لا من البداية.
 *
 * وهي مستقلة عن ترتيب البذور: تُشغّل بذرة شجرة الحسابات بنفسها (وهي عديمة
 * الأثر التراكمي) قبل النقل، لأن بعض أكواد الوجهة أُضيفت للشجرة في نفس الإصدار
 * والبذور تعمل بعد الترحيلات — فكانت تلك الأكواد غائبة لحظة النقل.
 */
return new class extends Migration
{
    /**
     * خريطة النقل: كود قديم في accounts ⇒ كود USALI في chart_of_accounts.
     * الحسابات الأب (1000، 2000…) لا تُرحَّل عليها قيود، لكنها مذكورة للاكتمال.
     */
    private const CODE_MAP = [
        // النقدية
        '1110' => '1111',   // نقدية الورديات            ⇒ درج نقدية الوردية
        '1120' => '1120',   // الصندوق العام             ⇒ الصندوق العام (نفسه)
        // الذمم المدينة
        '1200' => '1210',   // ديون النزلاء              ⇒ ذمم النزلاء المقيمين
        '1300' => '1265',   // ديون المشتريات (بقالة)    ⇒ ذمم مشتريات نيابةً عن الغير
        // الخصوم
        '2100' => '2410',   // رواتب مستحقة              ⇒ رواتب وأجور مستحقة
        '2200' => '2150',   // مصروفات مستحقة            ⇒ ذمم دائنة أخرى
        '2300' => '2230',   // مبالغ متبقية للنزلاء      ⇒ أرصدة نزلاء دائنة
        // حقوق الملكية
        '3100' => '3110',   // رأس المال                 ⇒ رأس المال المدفوع
        // الإيرادات
        '4100' => '4110',   // إيرادات الغرف             ⇒ إيراد السعر المعلن
        '4200' => '4660',   // إيرادات تعويض أضرار       ⇒ إيراد تعويض الأضرار
        // المصروفات
        '5100' => '6330',   // صيانة                     ⇒ صيانة وإصلاح المعدات
        '5200' => '6410',   // كهرباء/مياه               ⇒ الكهرباء
        '5300' => '6113',   // رواتب                     ⇒ رواتب وأجور أساسية — الإدارة
        '5400' => '5140',   // نظافة                     ⇒ مواد التنظيف
        '5500' => '5210',   // طعام وشراب                ⇒ تكلفة الأطعمة المباعة
        '5600' => '6190',   // أخرى                      ⇒ مصروفات إدارية أخرى
        // الحسابات الأب — لا قيود عليها، تُنسب لجذورها المقابلة احتياطاً
        '1000' => '1000', '1100' => '1100', '2000' => '2000',
        '3000' => '3000', '4000' => '4000', '5000' => '5000',
    ];

    /** يُستعمل عند التراجع: عكس الخريطة، مع تفضيل أول أصل لكل كود جديد. */
    private const REVERSE_FALLBACK = '5600';

    public function up(): void
    {
        // أُنجزت سابقاً: العمود القديم مُزال فلا شيء لنقله
        if (!Schema::hasColumn('journal_lines', 'account_id')) {
            return;
        }

        // أكواد الوجهة يجب أن تكون موجودة قبل النقل. البذرة عديمة الأثر التراكمي
        // (updateOrCreate على code) فتشغيلها هنا آمن، ويرفع الاعتماد على ترتيب
        // البذور الذي أسقط المحاولة الأولى. ولا تُشغَّل على استضافة جديدة بلا
        // قيود: لا شيء لتُنسَب أكوادُه، وبذرة النشر هي صاحبة الشجرة هناك.
        if (DB::table('journal_lines')->exists()) {
            (new ChartOfAccountsSeeder())->run();
        }

        $before = $this->ledgerTotals();

        if (!Schema::hasColumn('journal_lines', 'account_code')) {
            Schema::table('journal_lines', function (Blueprint $table) {
                $table->string('account_code', 20)->nullable()->after('account_id');
            });
        }

        if (!$this->hasIndex('journal_lines', 'journal_lines_account_code_index')) {
            Schema::table('journal_lines', function (Blueprint $table) {
                $table->index('account_code');
            });
        }

        $this->repointLines();

        // لا يُسمح ببقاء سطر بلا حساب جديد: إما نُقل أو تسقط الترحيلة، ونُسمّي
        // الحسابات العالقة بأسمائها كي تُعرَف المشكلة من الرسالة وحدها
        $this->assertNoOrphans();

        $after = $this->ledgerTotals();
        if (round($before['debit'], 2) !== round($after['debit'], 2)
            || round($before['credit'], 2) !== round($after['credit'], 2)) {
            throw new RuntimeException(
                'توقفت الترحيلة: مجاميع المدين/الدائن تغيّرت أثناء النقل '
                . "(قبل: {$before['debit']}/{$before['credit']} — بعد: {$after['debit']}/{$after['credit']})."
            );
        }

        // محاولة سابقة قد تكون أسقطت المفتاح وتوقّفت قبل العمود، فنفصل الخطوتين
        $hadForeignKey = $this->hasForeignKeyOn('journal_lines', 'account_id');
        Schema::table('journal_lines', function (Blueprint $table) use ($hadForeignKey) {
            if ($hadForeignKey) {
                $table->dropForeign(['account_id']);
            }
            $table->dropColumn('account_id');
        });

        Schema::table('journal_lines', function (Blueprint $table) {
            $table->string('account_code', 20)->nullable(false)->change();
        });

        if (!$this->hasForeignKeyOn('journal_lines', 'account_code')) {
            Schema::table('journal_lines', function (Blueprint $table) {
                $table->foreign('account_code')->references('code')->on('chart_of_accounts');
            });
        }
    }

    /**
     * يرمي رسالة تُسمّي الحسابات القديمة التي لم تجد مقابلاً، وعدد سطور كلٍّ
     * منها. الرسالة السابقة كانت تقول العدد فقط، فلا تدلّ على سبب التوقف.
     */
    private function assertNoOrphans(): void
    {
        $orphans = DB::table('journal_lines')
            ->leftJoin('accounts', 'accounts.id', '=', 'journal_lines.account_id')
            ->whereNull('journal_lines.account_code')
            ->groupBy('accounts.code', 'accounts.name')
            ->select('accounts.code', 'accounts.name', DB::raw('COUNT(*) as lines_count'))
            ->get();

        if ($orphans->isEmpty()) {
            return;
        }

        $details = $orphans
            ->map(fn ($row) => ($row->code ?? 'بلا حساب') . ' (' . ($row->name ?? '—') . '): '
                . $row->lines_count . ' سطر')
            ->implode('، ');

        throw new RuntimeException(
            'توقفت الترحيلة: حسابات قديمة بلا مقابل في شجرة USALI — ' . $details . '. '
            . 'أضف تحويلها إلى خريطة النقل في هذه الترحيلة ثم أعد المحاولة — لم يُحذف أي بيان.'
        );
    }

    /** فحص وجود فهرس باسمه — الأسماء ثابتة باصطلاح Laravel. */
    private function hasIndex(string $table, string $index): bool
    {
        try {
            foreach (Schema::getIndexes($table) as $existing) {
                if (($existing['name'] ?? null) === $index) {
                    return true;
                }
            }
        } catch (\Throwable $e) {
            // إصدار لا يدعم القراءة: نفترض غيابه ونترك الإنشاء يُخطئ إن تكرّر
        }

        return false;
    }

    /** فحص وجود مفتاح أجنبي على عمود بعينه. */
    private function hasForeignKeyOn(string $table, string $column): bool
    {
        try {
            foreach (Schema::getForeignKeys($table) as $foreign) {
                if (in_array($column, $foreign['columns'] ?? [], true)) {
                    return true;
                }
            }
        } catch (\Throwable $e) {
            // كما أعلاه
        }

        return false;
    }

    public function down(): void
    {
        Schema::table('journal_lines', function (Blueprint $table) {
            $table->foreignId('account_id')->nullable()->after('journal_entry_id')
                  ->constrained('accounts')->nullOnDelete();
        });

        // العكس بأفضل تطابق: أول كود قديم يشير للكود الجديد، وإلا "أخرى"
        $reverse = [];
        foreach (array_reverse(self::CODE_MAP, true) as $old => $new) {
            $reverse[$new] = $old;
        }

        $legacyIds = DB::table('accounts')->pluck('id', 'code');

        foreach (DB::table('journal_lines')->select('id', 'account_code')->get() as $line) {
            $legacyCode = $reverse[$line->account_code] ?? self::REVERSE_FALLBACK;
            DB::table('journal_lines')->where('id', $line->id)
                ->update(['account_id' => $legacyIds[$legacyCode] ?? null]);
        }

        Schema::table('journal_lines', function (Blueprint $table) {
            $table->dropForeign(['account_code']);
            $table->dropIndex(['account_code']);
            $table->dropColumn('account_code');
        });
    }

    /**
     * ينقل كل سطر إلى كوده الجديد اعتماداً على معرّف حسابه القديم — لا على
     * نصّ الكود، فالكود الواحد يعني حسابين مختلفين في الشجرتين.
     */
    private function repointLines(): void
    {
        if (!Schema::hasTable('accounts')) {
            return;
        }

        $known = DB::table('chart_of_accounts')->pluck('code')->flip();

        foreach (DB::table('accounts')->select('id', 'code')->get() as $legacy) {
            $newCode = self::CODE_MAP[$legacy->code] ?? null;

            if ($newCode === null || !$known->has($newCode)) {
                continue;   // يُلتقط لاحقاً كسطر يتيم فتسقط الترحيلة برسالة واضحة
            }

            DB::table('journal_lines')
                ->where('account_id', $legacy->id)
                ->update(['account_code' => $newCode]);
        }
    }

    /** @return array{debit: float, credit: float} */
    private function ledgerTotals(): array
    {
        $row = DB::table('journal_lines')
            ->selectRaw('COALESCE(SUM(debit),0) as d, COALESCE(SUM(credit),0) as c')
            ->first();

        return ['debit' => (float) $row->d, 'credit' => (float) $row->c];
    }
};
