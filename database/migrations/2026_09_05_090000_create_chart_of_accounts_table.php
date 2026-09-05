<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * شجرة الحسابات وفق معيار USALI للمنشآت الفندقية.
 * USALI-compliant hotel chart of accounts.
 *
 * الترابط الأبوي عبر code لا عبر id: الأكواد ثابتة ومعروفة محاسبياً
 * (1100، 4110…) فتبقى القيود والتقارير مقروءة دون الرجوع لمفاتيح داخلية.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->id();

            $table->string('code', 20);
            $table->string('parent_code', 20)->nullable();

            $table->string('name_en', 150);
            $table->string('name_ar', 150);

            $table->enum('type', ['asset', 'liability', 'equity', 'revenue', 'expense']);

            // contra_asset: مجمّع الإهلاك — أصل برصيد دائن
            // contra_revenue: الخصومات والمسموحات — إيراد برصيد مدين
            $table->enum('subtype', [
                'current', 'fixed', 'contra_asset', 'intangible',
                'current_liability', 'long_term_liability',
                'capital', 'retained',
                'operating', 'other_operated', 'contra_revenue',
                'payroll', 'cogs', 'undistributed', 'fixed_charges',
            ])->default('operating');

            $table->enum('department', [
                'rooms', 'fnb', 'spa', 'laundry', 'parking',
                'admin', 'sales', 'maintenance', 'utilities',
            ])->nullable();

            $table->boolean('is_posting')->default(false);
            $table->enum('normal_balance', ['debit', 'credit']);
            $table->boolean('is_active')->default(true);
            $table->unsignedTinyInteger('level')->default(1);

            $table->timestamps();

            $table->unique('code');
            $table->index('parent_code');
            $table->index(['type', 'is_posting']);
            $table->index(['department', 'is_active']);
            $table->index('level');

            // مرجع ذاتي على code (يتطلب الفهرس الفريد المعرَّف أعلاه)
            $table->foreign('parent_code')
                  ->references('code')->on('chart_of_accounts')
                  ->nullOnDelete()
                  ->cascadeOnUpdate();
        });

        // قيود سلامة على مستوى القاعدة. sqlite لا يدعم إضافة CHECK بعد الإنشاء،
        // فتُطبَّق على mysql/mariadb، ويحرسها الموديل والاختبارات في كل السائقين.
        if (in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement(
                'ALTER TABLE chart_of_accounts ADD CONSTRAINT chk_coa_level
                 CHECK (level BETWEEN 1 AND 4)'
            );

            // الترحيل مسموح على أوراق الشجرة فقط (مستوى 3 أو 4)
            DB::statement(
                'ALTER TABLE chart_of_accounts ADD CONSTRAINT chk_coa_posting_leaf
                 CHECK (is_posting = 0 OR level >= 3)'
            );

            // الرصيد الطبيعي يتبع طبيعة الحساب، مع استثناء الحسابات المقابلة
            DB::statement(
                "ALTER TABLE chart_of_accounts ADD CONSTRAINT chk_coa_normal_balance
                 CHECK (
                     subtype = 'contra_asset'
                     OR subtype = 'contra_revenue'
                     OR (type IN ('asset','expense')             AND normal_balance = 'debit')
                     OR (type IN ('liability','equity','revenue') AND normal_balance = 'credit')
                 )"
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('chart_of_accounts');
    }
};
