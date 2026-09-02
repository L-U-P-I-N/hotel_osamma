<?php
namespace App\Console\Commands;

use App\Models\Reservation;
use Illuminate\Console\Command;

/**
 * يكشف — ويصلح عند الطلب — أي انحراف بين "المدفوع" المخزَّن على الحجز
 * وبين مجموع دفعاته الفعلية ناقص استرجاعاته.
 *
 * سبب وجوده: قبل توحيد الحساب كان المدفوع يُعدَّل بالزيادة والنقصان، فأي
 * عملية قديمة لم تُعدّله تركت رقماً خاطئاً يظهر في تقارير الورديات كـ"مدفوع"
 * والنزيل عليه مديونية. هذا الأمر يصحّح ما تراكم من ذلك.
 */
class ReconcilePaidAmounts extends Command
{
    protected $signature = 'payments:reconcile
                            {--fix : تطبيق التصحيح فعلياً (بدونه عرض فقط)}
                            {--with-trashed : يشمل الحجوزات المحذوفة/الملغاة}';

    protected $description = 'كشف وتصحيح انحراف المدفوع عن مجموع الدفعات الفعلية';

    public function handle(): int
    {
        $apply = (bool) $this->option('fix');

        $query = Reservation::query()
            ->when($this->option('with-trashed'), fn($q) => $q->withTrashed())
            ->withSum('payments as payments_total', 'amount')
            ->withSum(['refunds as refunds_total' => fn($q) => $q->where('affects_paid_amount', true)], 'amount');

        $drifted = [];

        $query->chunkById(200, function ($reservations) use (&$drifted) {
            foreach ($reservations as $reservation) {
                $expected = max(0, round((float) ($reservation->payments_total ?? 0)
                                       - (float) ($reservation->refunds_total ?? 0), 2));
                $stored   = round((float) $reservation->paid_amount, 2);

                if (abs($expected - $stored) >= 0.01) {
                    $drifted[] = [
                        'id'       => $reservation->id,
                        'stored'   => $stored,
                        'expected' => $expected,
                        'model'    => $reservation,
                    ];
                }
            }
        });

        if (empty($drifted)) {
            $this->info('لا يوجد انحراف — المدفوع مطابق لمجموع الدفعات في كل الحجوزات.');
            return self::SUCCESS;
        }

        $this->warn('عدد الحجوزات المنحرفة: ' . count($drifted));
        $this->table(
            ['الحجز', 'المخزَّن', 'الصحيح', 'الفرق'],
            array_map(fn($row) => [
                $row['id'],
                number_format($row['stored'], 2),
                number_format($row['expected'], 2),
                number_format($row['expected'] - $row['stored'], 2),
            ], array_slice($drifted, 0, 50))
        );

        if (count($drifted) > 50) {
            $this->line('… و' . (count($drifted) - 50) . ' حجزاً آخر.');
        }

        if (!$apply) {
            $this->comment('عرض فقط. أضف ‎--fix‎ لتطبيق التصحيح.');
            return self::SUCCESS;
        }

        foreach ($drifted as $row) {
            $row['model']->recalculatePaidAmount();
        }

        $this->info('تم تصحيح ' . count($drifted) . ' حجزاً — وحُدِّثت حالة الدفع والمتبقي تبعاً لذلك.');

        return self::SUCCESS;
    }
}
