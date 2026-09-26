<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pdf_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');                       // اسم القالب كما يختاره المدير
            $table->string('report_key', 60);             // مفتاح التقرير من ReportRegistry
            $table->string('doc_title')->nullable();      // عنوان التصدير الظاهر في الترويسة
            $table->longText('body');                     // HTML آمن + عناصر نائبة
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['report_key', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pdf_templates');
    }
};
