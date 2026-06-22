<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->timestamp('settled_at')->nullable()->after('payment_method');
            $table->foreignId('settled_by')->nullable()->constrained('users')->nullOnDelete()->after('settled_at');
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['settled_by']);
            $table->dropColumn(['settled_at', 'settled_by']);
        });
    }
};
