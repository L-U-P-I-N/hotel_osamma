<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * شجرة الحسابات (Chart of Accounts) — أساس نظام القيود المزدوجة الموازي.
 * كل حساب له كود فريد هرمي (1000, 1100, 1110...) ونوع محاسبي وطبيعة رصيد
 * (مدين/دائن) تحدد كيف يُحسب رصيده من journal_lines.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->enum('type', ['asset', 'liability', 'equity', 'revenue', 'expense']);
            $table->foreignId('parent_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->enum('normal_balance', ['debit', 'credit']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
