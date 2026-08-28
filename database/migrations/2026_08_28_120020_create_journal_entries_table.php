<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * رأس القيد اليومي — يمثّل حركة مالية واحدة (دفعة، مصروف، راتب...) ويربط
 * بمصدرها التشغيلي عبر source_type/source_id لدعم الدرنة (drill-down) لاحقاً.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->date('entry_date');
            $table->string('description');
            $table->string('source_type');
            $table->unsignedBigInteger('source_id');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};
