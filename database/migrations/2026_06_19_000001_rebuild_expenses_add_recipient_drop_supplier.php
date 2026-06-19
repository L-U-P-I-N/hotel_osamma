<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->string('recipient_name', 255)->nullable()->after('amount');
        });

        // Nullify supplier_id then drop the column
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->dropColumn('supplier_id');
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn('recipient_name');
            $table->unsignedBigInteger('supplier_id')->nullable()->after('amount');
            $table->foreign('supplier_id')->references('id')->on('suppliers')->nullOnDelete();
        });
    }
};
