<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('hotels')
            ->where('name', 'فندق أسامة')
            ->update(['name' => 'فندق السعودي']);
    }

    public function down(): void
    {
        DB::table('hotels')
            ->where('name', 'فندق السعودي')
            ->update(['name' => 'فندق أسامة']);
    }
};
