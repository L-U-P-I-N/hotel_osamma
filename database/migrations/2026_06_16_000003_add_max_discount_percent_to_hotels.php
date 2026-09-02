<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('hotels', function (Blueprint $table) {
            // سقف الخصم الذي يحدده المدير — 0 يعني الخصم موقوف تماماً
            $table->decimal('max_discount_percent', 5, 2)->default(0)->after('logo_path');
        });
    }

    public function down(): void
    {
        Schema::table('hotels', function (Blueprint $table) {
            $table->dropColumn('max_discount_percent');
        });
    }
};
