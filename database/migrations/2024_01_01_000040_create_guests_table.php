<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('guests', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('nationality')->nullable();
            $table->string('occupation')->nullable();
            $table->enum('id_type', ['passport','national_id','residence'])->default('national_id');
            $table->text('id_number');
            $table->string('id_issuer')->nullable();
            $table->date('id_issue_date')->nullable();
            $table->text('phone')->nullable();
            $table->string('id_image_path')->nullable();
            $table->boolean('is_blacklisted')->default(false);
            $table->text('blacklist_reason')->nullable();
            $table->timestamp('blacklisted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void { Schema::dropIfExists('guests'); }
};
