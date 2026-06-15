<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('companions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->string('full_name');
            $table->string('nationality')->nullable();
            $table->enum('id_type', ['passport','national_id','residence'])->default('national_id');
            $table->text('id_number')->nullable();
            $table->string('id_issuer')->nullable();
            $table->date('id_issue_date')->nullable();
            $table->enum('relationship', ['wife','son','daughter','brother','sister','father','mother','other'])->default('other');
            $table->string('id_image_path')->nullable();
            $table->string('marriage_doc_path')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void { Schema::dropIfExists('companions'); }
};
