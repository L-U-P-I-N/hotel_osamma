<?php
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            // السعر الليلي الفعلي المتفق عليه (يشمل مضاعف الجناح/الشقة)
            $table->decimal('nightly_price', 10, 2)->default(0)->after('suite_booking_type');
            $table->decimal('discount_amount', 10, 2)->default(0)->after('total_amount');
            $table->string('discount_reason')->nullable()->after('discount_amount');
            $table->foreignId('discounted_by')->nullable()->after('discount_reason')
                  ->references('id')->on('users')->nullOnDelete();
            $table->timestamp('discounted_at')->nullable()->after('discounted_by');
        });

        // اشتقاق السعر الليلي للحجوزات القديمة في PHP بدل SQL،
        // لأن دوال التواريخ تختلف بين sqlite و mysql وكلاهما مستخدم هنا.
        DB::table('reservations')
            ->select('id', 'check_in_date', 'check_out_date', 'total_amount')
            ->orderBy('id')
            ->chunkById(500, function ($reservations) {
                foreach ($reservations as $reservation) {
                    $nights = max(1, Carbon::parse($reservation->check_in_date)
                        ->diffInDays(Carbon::parse($reservation->check_out_date)));
                    DB::table('reservations')
                        ->where('id', $reservation->id)
                        ->update(['nightly_price' => round((float) $reservation->total_amount / $nights, 2)]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropForeign(['discounted_by']);
            $table->dropColumn(['nightly_price', 'discount_amount', 'discount_reason', 'discounted_by', 'discounted_at']);
        });
    }
};
