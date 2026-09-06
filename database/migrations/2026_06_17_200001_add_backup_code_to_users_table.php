<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('backup_code')->nullable()->unique()->after('password');
        });
    }

    public function down(): void
    {
        // نفس مشكلة sqlite: حذف عمود عليه فهرس فريد مباشرة يفشل بخطأ
        // "no such column" أثناء إعادة بناء الجدول الداخلية — يُحذف الفهرس أولاً.
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['backup_code']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('backup_code');
        });
    }
};
