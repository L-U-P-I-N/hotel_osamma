<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * سجل مفصّل لفترات محاسبة الغرفة: الحجز الأولي وكل تجديد كسطرٍ مستقل بتاريخه
     * وسعره وعدد لياليه ومبلغه. يتيح عرض تفصيل واضح لأسعار الغرفة (بدل متوسط
     * الليلة المربك) يُجمَع دائماً مطابقاً لإجمالي الغرفة قبل الخصم.
     */
    public function up(): void
    {
        Schema::create('reservation_segments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained('reservations')->cascadeOnDelete();
            $table->enum('type', ['initial', 'renewal'])->default('initial');
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedInteger('nights')->default(1);
            $table->decimal('price_per_night', 12, 2)->default(0);
            $table->decimal('amount', 12, 2)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['reservation_id', 'start_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_segments');
    }
};
