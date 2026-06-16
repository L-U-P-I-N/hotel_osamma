<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // SQLite requires table rebuild to change column type/nullability
        Schema::table('shifts', function (Blueprint $table) {
            $table->string('shift_type')->nullable()->change();
        });

        // Clear old shift_type values
        \Illuminate\Support\Facades\DB::table('shifts')->update(['shift_type' => null]);
    }

    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->string('shift_type')->nullable(false)->change();
        });
    }
};
