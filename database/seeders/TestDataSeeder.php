<?php
namespace Database\Seeders;

use App\Models\CashWithdrawal;
use App\Models\Companion;
use App\Models\Guest;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\Shift;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        // User IDs: 1=admin, 2=receptionist, 3=accountant
        // Room IDs based on seeded rooms

        // ─── الضيوف ───────────────────────────────────────────────────────────
        $guests = $this->seedGuests();

        // ─── الورديات السابقة ─────────────────────────────────────────────────
        $shifts = $this->seedShifts();

        // ─── الحجوزات ─────────────────────────────────────────────────────────
        $this->seedReservations($guests, $shifts);
    }

    private function seedGuests(): array
    {
        $data = [
            ['full_name' => 'أحمد محمد علي الشرجبي',    'nationality' => 'يمني',   'id_type' => 'national_id', 'id_number' => '1001234567', 'id_issuer' => 'وزارة الداخلية - صنعاء',     'phone' => '0712345678', 'occupation' => 'مهندس'],
            ['full_name' => 'محمد عبدالله سالم باجابر',  'nationality' => 'يمني',   'id_type' => 'national_id', 'id_number' => '1009876543', 'id_issuer' => 'وزارة الداخلية - عدن',       'phone' => '0733456789', 'occupation' => 'تاجر'],
            ['full_name' => 'عبدالرحمن خالد النجار',     'nationality' => 'سعودي',  'id_type' => 'national_id', 'id_number' => '1054321098', 'id_issuer' => 'وزارة الداخلية - الرياض',   'phone' => '0551234567', 'occupation' => 'موظف حكومي'],
            ['full_name' => 'حسين علي محمد الأهدل',      'nationality' => 'يمني',   'id_type' => 'national_id', 'id_number' => '1003456789', 'id_issuer' => 'وزارة الداخلية - حجة',      'phone' => '0712987654', 'occupation' => 'طبيب'],
            ['full_name' => 'فيصل عبدالعزيز الغامدي',    'nationality' => 'سعودي',  'id_type' => 'passport',    'id_number' => 'SA20345678',  'id_issuer' => 'الجوازات السعودية',          'phone' => '0504567890', 'occupation' => 'رجل أعمال'],
            ['full_name' => 'عمر إبراهيم حسن الصومالي',  'nationality' => 'صومالي', 'id_type' => 'passport',    'id_number' => 'SO12345678',  'id_issuer' => 'Somalia Immigration',        'phone' => '0772345678', 'occupation' => 'تاجر'],
            ['full_name' => 'علي حمود قاسم المقطري',     'nationality' => 'يمني',   'id_type' => 'national_id', 'id_number' => '1007654321', 'id_issuer' => 'وزارة الداخلية - تعز',      'phone' => '0737890123', 'occupation' => 'مقاول'],
            ['full_name' => 'محمد أحمد يحيى الورافي',    'nationality' => 'يمني',   'id_type' => 'national_id', 'id_number' => '1005432109', 'id_issuer' => 'وزارة الداخلية - إب',       'phone' => '0715678901', 'occupation' => 'معلم'],
            ['full_name' => 'سالم ناصر البلوشي',         'nationality' => 'أردني',  'id_type' => 'passport',    'id_number' => 'JO98765432',  'id_issuer' => 'Jordan Passports Dept',      'phone' => '0790123456', 'occupation' => 'مستشار'],
            ['full_name' => 'أنور علي الصباحي',          'nationality' => 'مصري',   'id_type' => 'passport',    'id_number' => 'EG55667788',  'id_issuer' => 'Egypt Immigration',          'phone' => '0106789012', 'occupation' => 'مهندس'],
            ['full_name' => 'طارق محمد الدوسري',         'nationality' => 'سعودي',  'id_type' => 'national_id', 'id_number' => '1076543210', 'id_issuer' => 'وزارة الداخلية - جدة',      'phone' => '0561234567', 'occupation' => 'طالب'],
            ['full_name' => 'ياسر عبده حمود الشامي',     'nationality' => 'يمني',   'id_type' => 'national_id', 'id_number' => '1008765432', 'id_issuer' => 'وزارة الداخلية - صنعاء',   'phone' => '0714567890', 'occupation' => 'موظف'],
        ];

        $created = [];
        foreach ($data as $d) {
            $created[] = Guest::create($d);
        }
        return $created;
    }

    private function seedShifts(): array
    {
        $shifts = [];

        // 5 ورديات سابقة مغلقة للموظف (user_id=2)
        $shiftDefs = [
            ['date' => now()->subDays(6), 'type' => 'morning', 'recv_yer' => 45000, 'wdr_yer' => 5000],
            ['date' => now()->subDays(5), 'type' => 'evening', 'recv_yer' => 62000, 'wdr_yer' => 0],
            ['date' => now()->subDays(4), 'type' => 'morning', 'recv_yer' => 38000, 'wdr_yer' => 10000],
            ['date' => now()->subDays(3), 'type' => 'morning', 'recv_yer' => 55000, 'wdr_yer' => 0],
            ['date' => now()->subDays(2), 'type' => 'evening', 'recv_yer' => 72000, 'wdr_yer' => 15000],
            ['date' => now()->subDays(1), 'type' => 'morning', 'recv_yer' => 48000, 'wdr_yer' => 0],
        ];

        foreach ($shiftDefs as $def) {
            $start = $def['date']->copy()->setHour($def['type'] === 'morning' ? 7 : ($def['type'] === 'evening' ? 15 : 23));
            $end   = $start->copy()->addHours(8);
            $s = Shift::create([
                'user_id'               => 2,
                'shift_date'            => $def['date']->toDateString(),
                'shift_type'            => $def['type'],
                'started_at'            => $start,
                'ended_at'              => $end,
                'closed_at'             => $end,
                'is_closed'             => true,
                'total_received_yer'    => $def['recv_yer'],
                'total_received_sar'    => 0,
                'total_received_usd'    => 0,
                'total_withdrawals_yer' => $def['wdr_yer'],
                'total_withdrawals_sar' => 0,
                'total_withdrawals_usd' => 0,
            ]);
            $shifts[] = $s;

            // سحبيات للورديات التي لديها سحبيات
            if ($def['wdr_yer'] > 0) {
                CashWithdrawal::create([
                    'shift_id'          => $s->id,
                    'amount'            => $def['wdr_yer'],
                    'currency'          => 'YER',
                    'withdrawal_date'   => $end,
                    'withdrawn_by_name' => 'المدير',
                    'handed_by_name'    => 'موظف الاستقبال',
                    'notes'             => 'سحب يومي',
                ]);
            }
        }

        // ورديات للمحاسب (user_id=3)
        $accountShifts = [
            ['date' => now()->subDays(3), 'type' => 'evening', 'recv_yer' => 35000],
            ['date' => now()->subDays(1), 'type' => 'evening', 'recv_yer' => 41000],
        ];
        foreach ($accountShifts as $def) {
            $start = $def['date']->copy()->setHour(15);
            $end   = $start->copy()->addHours(8);
            $s = Shift::create([
                'user_id'            => 3,
                'shift_date'         => $def['date']->toDateString(),
                'shift_type'         => $def['type'],
                'started_at'         => $start,
                'ended_at'           => $end,
                'closed_at'          => $end,
                'is_closed'          => true,
                'total_received_yer' => $def['recv_yer'],
                'total_withdrawals_yer' => 0,
            ]);
            $shifts[] = $s;
        }

        return $shifts;
    }

    private function seedReservations(array $guests, array $shifts): void
    {
        // ─── حجوزات مسجّل خروج (تاريخية) ────────────────────────────────────
        $historical = [
            // غرفة 202 قبل أسبوع
            [
                'guest_idx'    => 0, // أحمد الشرجبي
                'room_id'      => 24, // 202
                'check_in'     => now()->subDays(10),
                'check_out'    => now()->subDays(7),
                'nights'       => 3,
                'price'        => 15000,
                'paid'         => 45000,
                'pay_method'   => 'cash',
                'created_by'   => 2,
                'purpose'      => 'علاج',
                'origin'       => 'تعز',
                'companions'   => [
                    ['full_name' => 'فاطمة أحمد الشرجبي', 'nationality' => 'يمني', 'id_type' => 'national_id', 'id_number' => '1501234567', 'relationship' => 'wife'],
                ],
            ],
            // غرفة 303 قبل 5 أيام
            [
                'guest_idx'    => 1, // محمد باجابر
                'room_id'      => 35, // 303
                'check_in'     => now()->subDays(8),
                'check_out'    => now()->subDays(5),
                'nights'       => 3,
                'price'        => 15000,
                'paid'         => 45000,
                'pay_method'   => 'cash',
                'created_by'   => 2,
                'purpose'      => 'سياحة',
                'origin'       => 'عدن',
                'companions'   => [],
            ],
            // غرفة 401A+401B جناح كامل
            [
                'guest_idx'    => 2, // عبدالرحمن النجار - سعودي
                'room_id'      => 42, // 401A
                'linked_room_id' => 43, // 401B
                'suite_booking_type' => 'both',
                'check_in'     => now()->subDays(5),
                'check_out'    => now()->subDays(2),
                'nights'       => 3,
                'price'        => 50000, // 25000 * 2
                'paid'         => 150000,
                'pay_method'   => 'bank_transfer',
                'created_by'   => 1,
                'purpose'      => 'سياحة',
                'origin'       => 'الرياض',
                'companions'   => [
                    ['full_name' => 'نورة خالد النجار', 'nationality' => 'سعودي', 'id_type' => 'national_id', 'id_number' => '2054321098', 'relationship' => 'wife'],
                    ['full_name' => 'عبدالله عبدالرحمن النجار', 'nationality' => 'سعودي', 'id_type' => 'national_id', 'id_number' => '3054321098', 'relationship' => 'son'],
                    ['full_name' => 'سارة عبدالرحمن النجار', 'nationality' => 'سعودي', 'id_type' => 'national_id', 'id_number' => '4054321098', 'relationship' => 'daughter'],
                ],
            ],
            // غرفة 504 - دفع جزئي
            [
                'guest_idx'    => 7, // محمد الورافي
                'room_id'      => 56, // 504
                'check_in'     => now()->subDays(4),
                'check_out'    => now()->subDays(1),
                'nights'       => 3,
                'price'        => 15000,
                'paid'         => 30000,
                'pay_method'   => 'cash',
                'created_by'   => 2,
                'purpose'      => 'عمل',
                'origin'       => 'إب',
                'companions'   => [],
            ],
        ];

        foreach ($historical as $r) {
            $nights    = $r['check_in']->diffInDays($r['check_out']);
            $total     = $nights * $r['price'];
            $payment_status = $r['paid'] >= $total ? 'paid' : 'partial';

            $reservation = Reservation::create([
                'guest_id'          => $guests[$r['guest_idx']]->id,
                'room_id'           => $r['room_id'],
                'linked_room_id'    => $r['linked_room_id'] ?? null,
                'suite_booking_type'=> $r['suite_booking_type'] ?? null,
                'created_by'        => $r['created_by'],
                'check_in_date'     => $r['check_in']->toDateString(),
                'check_out_date'    => $r['check_out']->toDateString(),
                'actual_check_out'  => $r['check_out']->setHour(12),
                'origin'            => $r['origin'],
                'purpose'           => $r['purpose'],
                'status'            => 'checked_out',
                'payment_status'    => $payment_status,
                'total_amount'      => $total,
                'paid_amount'       => $r['paid'],
            ]);

            Payment::create([
                'reservation_id' => $reservation->id,
                'shift_id'       => $shifts[0]->id,
                'received_by'    => $r['created_by'],
                'amount'         => $r['paid'],
                'currency'       => 'YER',
                'method'         => $r['pay_method'],
                'payment_date'   => $r['check_in']->addHour(),
                'type'           => 'room',
            ]);

            foreach ($r['companions'] as $c) {
                Companion::create([
                    'reservation_id' => $reservation->id,
                    'full_name'      => $c['full_name'],
                    'nationality'    => $c['nationality'],
                    'id_type'        => $c['id_type'],
                    'id_number'      => $c['id_number'],
                    'relationship'   => $c['relationship'],
                ]);
            }
        }

        // ─── حجوزات مسجّل دخول حالياً (checked_in) ───────────────────────────
        $checkedIn = [
            [
                'guest_idx'  => 3, // حسين الأهدل
                'room_id'    => 25, // 203
                'check_in'   => now()->subDays(2),
                'check_out'  => now()->addDays(3),
                'price'      => 15000,
                'paid'       => 30000,
                'pay_method' => 'cash',
                'created_by' => 2,
                'purpose'    => 'علاج',
                'origin'     => 'حجة',
                'companions' => [],
            ],
            [
                'guest_idx'  => 4, // فيصل الغامدي - سعودي
                'room_id'    => 32, // 301A
                'linked_room_id' => 33, // 301B
                'suite_booking_type' => 'both',
                'check_in'   => now()->subDays(1),
                'check_out'  => now()->addDays(4),
                'price'      => 50000,
                'paid'       => 50000,
                'pay_method' => 'pos',
                'created_by' => 1,
                'purpose'    => 'سياحة',
                'origin'     => 'جدة',
                'companions' => [
                    ['full_name' => 'منيرة فيصل الغامدي', 'nationality' => 'سعودي', 'id_type' => 'national_id', 'id_number' => '2076543210', 'relationship' => 'wife'],
                ],
            ],
            [
                'guest_idx'  => 5, // عمر الصومالي
                'room_id'    => 44, // 402
                'check_in'   => today(),
                'check_out'  => now()->addDays(2),
                'price'      => 15000,
                'paid'       => 15000,
                'pay_method' => 'cash',
                'created_by' => 2,
                'purpose'    => 'عمل',
                'origin'     => 'موغاديشو',
                'companions' => [],
            ],
            [
                'guest_idx'  => 6, // علي المقطري
                'room_id'    => 54, // 502
                'check_in'   => today(),
                'check_out'  => now()->addDays(5),
                'price'      => 15000,
                'paid'       => 75000,
                'pay_method' => 'cash',
                'created_by' => 2,
                'purpose'    => 'سياحة',
                'origin'     => 'تعز',
                'companions' => [
                    ['full_name' => 'أمل علي المقطري', 'nationality' => 'يمني', 'id_type' => 'national_id', 'id_number' => '1507654321', 'relationship' => 'wife'],
                    ['full_name' => 'أحمد علي المقطري', 'nationality' => 'يمني', 'id_type' => 'national_id', 'id_number' => '1607654321', 'relationship' => 'son'],
                ],
            ],
            [
                'guest_idx'  => 9, // أنور الصباحي - مصري
                'room_id'    => 64, // 602
                'check_in'   => now()->subDays(1),
                'check_out'  => now()->addDays(6),
                'price'      => 15000,
                'paid'       => 15000,
                'pay_method' => 'bank_transfer',
                'created_by' => 3,
                'purpose'    => 'عمل',
                'origin'     => 'القاهرة',
                'companions' => [],
            ],
        ];

        foreach ($checkedIn as $r) {
            $checkIn  = $r['check_in'] instanceof Carbon ? $r['check_in'] : Carbon::parse($r['check_in']);
            $checkOut = $r['check_out'] instanceof Carbon ? $r['check_out'] : Carbon::parse($r['check_out']);
            $nights   = $checkIn->diffInDays($checkOut);
            $total    = $nights * $r['price'];
            $paymentStatus = $r['paid'] >= $total ? 'paid' : ($r['paid'] > 0 ? 'partial' : 'unpaid');

            $reservation = Reservation::create([
                'guest_id'           => $guests[$r['guest_idx']]->id,
                'room_id'            => $r['room_id'],
                'linked_room_id'     => $r['linked_room_id'] ?? null,
                'suite_booking_type' => $r['suite_booking_type'] ?? null,
                'created_by'         => $r['created_by'],
                'check_in_date'      => $checkIn->toDateString(),
                'check_out_date'     => $checkOut->toDateString(),
                'origin'             => $r['origin'],
                'purpose'            => $r['purpose'],
                'status'             => 'checked_in',
                'payment_status'     => $paymentStatus,
                'total_amount'       => $total,
                'paid_amount'        => $r['paid'],
            ]);

            // تحديث حالة الغرفة إلى occupied
            Room::whereIn('id', array_filter([$r['room_id'], $r['linked_room_id'] ?? null]))
                ->update(['status' => 'occupied']);

            if ($r['paid'] > 0) {
                Payment::create([
                    'reservation_id' => $reservation->id,
                    'shift_id'       => $shifts[count($shifts) - 1]->id,
                    'received_by'    => $r['created_by'],
                    'amount'         => $r['paid'],
                    'currency'       => 'YER',
                    'method'         => $r['pay_method'],
                    'payment_date'   => $checkIn->addHour(),
                    'type'           => 'room',
                ]);
            }

            foreach ($r['companions'] as $c) {
                Companion::create([
                    'reservation_id' => $reservation->id,
                    'full_name'      => $c['full_name'],
                    'nationality'    => $c['nationality'],
                    'id_type'        => $c['id_type'],
                    'id_number'      => $c['id_number'],
                    'relationship'   => $c['relationship'],
                ]);
            }
        }

        // ─── حجوزات مؤكدة مستقبلية (confirmed) ───────────────────────────────
        $confirmed = [
            [
                'guest_idx'  => 8, // سالم البلوشي - أردني
                'room_id'    => 36, // 304
                'check_in'   => now()->addDays(2),
                'check_out'  => now()->addDays(5),
                'price'      => 15000,
                'paid'       => 0,
                'created_by' => 1,
                'purpose'    => 'سياحة',
                'origin'     => 'عمّان',
            ],
            [
                'guest_idx'  => 10, // طارق الدوسري
                'room_id'    => 45, // 403
                'check_in'   => now()->addDays(3),
                'check_out'  => now()->addDays(7),
                'price'      => 15000,
                'paid'       => 30000,
                'pay_method' => 'cash',
                'created_by' => 2,
                'purpose'    => 'دراسة',
                'origin'     => 'جدة',
            ],
            [
                'guest_idx'  => 11, // ياسر الشامي
                'room_id'    => 65, // 603
                'check_in'   => now()->addDays(1),
                'check_out'  => now()->addDays(4),
                'price'      => 15000,
                'paid'       => 0,
                'created_by' => 2,
                'purpose'    => 'عمل',
                'origin'     => 'صنعاء',
            ],
        ];

        foreach ($confirmed as $r) {
            $checkIn  = Carbon::parse($r['check_in']);
            $checkOut = Carbon::parse($r['check_out']);
            $nights   = $checkIn->diffInDays($checkOut);
            $total    = $nights * $r['price'];
            $paymentStatus = ($r['paid'] ?? 0) >= $total && $total > 0 ? 'paid' : (($r['paid'] ?? 0) > 0 ? 'partial' : 'unpaid');

            $reservation = Reservation::create([
                'guest_id'       => $guests[$r['guest_idx']]->id,
                'room_id'        => $r['room_id'],
                'created_by'     => $r['created_by'],
                'check_in_date'  => $checkIn->toDateString(),
                'check_out_date' => $checkOut->toDateString(),
                'origin'         => $r['origin'],
                'purpose'        => $r['purpose'],
                'status'         => 'confirmed',
                'payment_status' => $paymentStatus,
                'total_amount'   => $total,
                'paid_amount'    => $r['paid'] ?? 0,
            ]);

            // تحديث حالة الغرفة إلى reserved
            Room::where('id', $r['room_id'])->update(['status' => 'reserved']);

            if (($r['paid'] ?? 0) > 0) {
                Payment::create([
                    'reservation_id' => $reservation->id,
                    'shift_id'       => $shifts[count($shifts) - 1]->id,
                    'received_by'    => $r['created_by'],
                    'amount'         => $r['paid'],
                    'currency'       => 'YER',
                    'method'         => $r['pay_method'] ?? 'cash',
                    'payment_date'   => now(),
                    'type'           => 'room',
                ]);
            }
        }

        // ─── حجز ملغي ─────────────────────────────────────────────────────────
        Reservation::create([
            'guest_id'       => $guests[7]->id,
            'room_id'        => 26, // 204
            'created_by'     => 2,
            'check_in_date'  => now()->subDays(2)->toDateString(),
            'check_out_date' => now()->addDay()->toDateString(),
            'origin'         => 'إب',
            'purpose'        => 'علاج',
            'status'         => 'cancelled',
            'payment_status' => 'unpaid',
            'total_amount'   => 45000,
            'paid_amount'    => 0,
        ]);

        // ─── غرف تحت الصيانة ─────────────────────────────────────────────────
        Room::where('id', 28)->update(['status' => 'maintenance']);   // 206
        Room::where('id', 38)->update(['status' => 'under_inspection']); // 306

        // ─── تحديث إجماليات الورديات بناءً على الدفعات الفعلية ──────────────
        foreach ($shifts as $shift) {
            $total = Payment::where('shift_id', $shift->id)->sum('amount');
            if ($total > 0) {
                $shift->update(['total_received_yer' => $total]);
            }
        }
    }
}
