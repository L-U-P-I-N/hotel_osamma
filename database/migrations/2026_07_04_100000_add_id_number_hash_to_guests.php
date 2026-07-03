<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Guest;

return new class extends Migration
{
    /**
     * guests.id_number مُشفَّر (encrypted cast) بتشفير غير حتمي (IV عشوائي في كل
     * حفظ)، فأي WHERE id_number = ? مباشر على العمود لا يطابق شيئاً أبداً —
     * هذا العمود الجديد "بصمة" (hash) حتمية للرقم تُستخدم للبحث المطابق بدلاً
     * من العمود المشفَّر نفسه (نمط "blind index" القياسي للبحث في بيانات مشفَّرة).
     */
    public function up(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            $table->string('id_number_hash', 64)->nullable()->after('id_number')->index();
        });

        // إعادة حساب البصمة لكل السجلات الحالية حتى يعمل البحث فوراً عليها أيضاً
        Guest::withTrashed()->whereNotNull('id_number')->chunkById(200, function ($guests) {
            foreach ($guests as $guest) {
                if ($guest->id_number) {
                    $guest->newQueryWithoutScopes()
                        ->whereKey($guest->id)
                        ->update(['id_number_hash' => hash('sha256', $guest->id_number)]);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            $table->dropColumn('id_number_hash');
        });
    }
};
