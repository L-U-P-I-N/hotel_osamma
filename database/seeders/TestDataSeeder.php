<?php
namespace Database\Seeders;

use App\Models\CashWithdrawal;
use App\Models\Companion;
use App\Models\Guest;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class TestDataSeeder extends Seeder
{
    // map room_number => Room model (loaded once)
    private array $rooms = [];

    public function run(): void
    {
        // Load all rooms by room_number for lookup
        Room::all()->each(fn($r) => $this->rooms[$r->room_number] = $r);

        // Resolve user IDs dynamically
        $admin       = User::role('admin')->first();
        $receptionist = User::role('receptionist')->first();
        $accountant   = User::where('username', 'accountant')->orWhere('name', 'LIKE', '%محاسب%')->first()
                        ?? $receptionist;

        $adminId      = $admin?->id       ?? 1;
        $receptionId  = $receptionist?->id ?? 2;
        $accountantId = $accountant?->id   ?? 3;

        $guests = $this->seedGuests();
        $shifts = $this->seedShifts($receptionId, $accountantId);
        $this->seedReservations($guests, $shifts, $adminId, $receptionId, $accountantId);
    }

    private function room(string $number): ?Room
    {
        return $this->rooms[$number] ?? null;
    }

    private function seedGuests(): array
    {
        $data = [
            ['full_name' => 'أحمد محمد علي الشرجبي',   'nationality' => 'يمني',   'id_type' => 'national_id', 'id_number' => '1001234567', 'id_issuer' => 'وزارة الداخلية - صنعاء',   'phone' => '0712345678', 'occupation' => 'مهندس'],
            ['full_name' => 'محمد عبدالله سالم باجابر', 'nationality' => 'يمني',   'id_type' => 'national_id', 'id_number' => '1009876543', 'id_issuer' => 'وزارة الداخلية - عدن',     'phone' => '0733456789', 'occupation' => 'تاجر'],
            ['full_name' => 'عبدالرحمن خالد النجار',    'nationality' => 'سعودي',  'id_type' => 'national_id', 'id_number' => '1054321098', 'id_issuer' => 'وزارة الداخلية - الرياض', 'phone' => '0551234567', 'occupation' => 'موظف حكومي'],
            ['full_name' => 'حسين علي محمد الأهدل',     'nationality' => 'يمني',   'id_type' => 'national_id', 'id_number' => '1003456789', 'id_issuer' => 'وزارة الداخلية - حجة',    'phone' => '0712987654', 'occupation' => 'طبيب'],
            ['full_name' => 'فيصل عبدالعزيز الغامدي',   'nationality' => 'سعودي',  'id_type' => 'passport',    'id_number' => 'SA20345678',  'id_issuer' => 'الجوازات السعودية',        'phone' => '0504567890', 'occupation' => 'رجل أعمال'],
            ['full_name' => 'عمر إبراهيم حسن الصومالي', 'nationality' => 'صومالي', 'id_type' => 'passport',    'id_number' => 'SO12345678',  'id_issuer' => 'Somalia Immigration',      'phone' => '0772345678', 'occupation' => 'تاجر'],
            ['full_name' => 'علي حمود قاسم المقطري',    'nationality' => 'يمني',   'id_type' => 'national_id', 'id_number' => '1007654321', 'id_issuer' => 'وزارة الداخلية - تعز',    'phone' => '0737890123', 'occupation' => 'مقاول'],
            ['full_name' => 'محمد أحمد يحيى الورافي',   'nationality' => 'يمني',   'id_type' => 'national_id', 'id_number' => '1005432109', 'id_issuer' => 'وزارة الداخلية - إب',     'phone' => '0715678901', 'occupation' => 'معلم'],
            ['full_name' => 'سالم ناصر البلوشي',        'nationality' => 'أردني',  'id_type' => 'passport',    'id_number' => 'JO98765432',  'id_issuer' => 'Jordan Passports Dept',    'phone' => '0790123456', 'occupation' => 'مستشار'],
            ['full_name' => 'أنور علي الصباحي',         'nationality' => 'مصري',   'id_type' => 'passport',    'id_number' => 'EG55667788',  'id_issuer' => 'Egypt Immigration',        'phone' => '0106789012', 'occupation' => 'مهندس'],
            ['full_name' => 'طارق محمد الدوسري',        'nationality' => 'سعودي',  'id_type' => 'national_id', 'id_number' => '1076543210', 'id_issuer' => 'وزارة الداخلية - جدة',    'phone' => '0561234567', 'occupation' => 'طالب'],
            ['full_name' => 'ياسر عبده حمود الشامي',    'nationality' => 'يمني',   'id_type' => 'national_id', 'id_number' => '1008765432', 'id_issuer' => 'وزارة الداخلية - صنعاء',  'phone' => '0714567890', 'occupation' => 'موظف'],
        ];

        $created = [];
        foreach ($data as $d) {
            $created[] = Guest::create($d);
        }
        return $created;
    }

    private function seedShifts(int $receptionId, int $accountantId): array
    {
        $shifts = [];

        $shiftDefs = [
            ['user' => $receptionId,  'date' => now()->subDays(6), 'type' => 'morning', 'recv_yer' => 45000, 'wdr_yer' => 5000],
            ['user' => $receptionId,  'date' => now()->subDays(5), 'type' => 'evening', 'recv_yer' => 62000, 'wdr_yer' => 0],
            ['user' => $receptionId,  'date' => now()->subDays(4), 'type' => 'morning', 'recv_yer' => 38000, 'wdr_yer' => 10000],
            ['user' => $accountantId, 'date' => now()->subDays(3), 'type' => 'evening', 'recv_yer' => 35000, 'wdr_yer' => 0],
            ['user' => $receptionId,  'date' => now()->subDays(3), 'type' => 'morning', 'recv_yer' => 55000, 'wdr_yer' => 0],
            ['user' => $receptionId,  'date' => now()->subDays(2), 'type' => 'evening', 'recv_yer' => 72000, 'wdr_yer' => 15000],
            ['user' => $accountantId, 'date' => now()->subDays(1), 'type' => 'evening', 'recv_yer' => 41000, 'wdr_yer' => 0],
            ['user' => $receptionId,  'date' => now()->subDays(1), 'type' => 'morning', 'recv_yer' => 48000, 'wdr_yer' => 0],
        ];

        foreach ($shiftDefs as $def) {
            $hourMap = ['morning' => 7, 'evening' => 15, 'night' => 23];
            $start = $def['date']->copy()->setHour($hourMap[$def['type']] ?? 7)->setMinute(0)->setSecond(0);
            $end   = $start->copy()->addHours(8);

            $s = Shift::create([
                'user_id'               => $def['user'],
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

            $shifts[] = $s;
        }

        return $shifts;
    }

    private function makeReservation(array $r, array $guests, array $shifts): void
    {
        $roomModel  = $this->room($r['room']);
        $linkedModel = isset($r['linked_room']) ? $this->room($r['linked_room']) : null;

        if (!$roomModel) {
            $this->command->warn("Room {$r['room']} not found, skipping.");
            return;
        }

        $checkIn  = Carbon::parse($r['check_in']);
        $checkOut = Carbon::parse($r['check_out']);
        $nights   = max(1, $checkIn->diffInDays($checkOut));
        $total    = $nights * $r['price'];
        $paid     = $r['paid'] ?? 0;

        $paymentStatus = match(true) {
            $paid >= $total && $total > 0 => 'paid',
            $paid > 0 => 'partial',
            ($r['pay_status'] ?? '') === 'deferred' => 'deferred',
            default => 'unpaid',
        };

        $reservation = Reservation::create([
            'guest_id'           => $guests[$r['guest_idx']]->id,
            'room_id'            => $roomModel->id,
            'linked_room_id'     => $linkedModel?->id,
            'suite_booking_type' => $r['suite_booking_type'] ?? null,
            'created_by'         => $r['created_by'],
            'check_in_date'      => $checkIn->toDateString(),
            'check_out_date'     => $checkOut->toDateString(),
            'actual_check_out'   => isset($r['actual_checkout']) ? Carbon::parse($r['actual_checkout']) : null,
            'origin'             => $r['origin'],
            'purpose'            => $r['purpose'],
            'notes'              => $r['notes'] ?? null,
            'status'             => $r['status'],
            'payment_status'     => $paymentStatus,
            'total_amount'       => $total,
            'paid_amount'        => $paid,
        ]);

        // Update room status
        $newStatus = match($r['status']) {
            'checked_in' => 'occupied',
            'confirmed'  => 'reserved',
            default      => null,
        };
        if ($newStatus) {
            $roomModel->update(['status' => $newStatus]);
            $linkedModel?->update(['status' => $newStatus]);
        }

        // Payment
        if ($paid > 0) {
            $shift = $shifts[count($shifts) - 1];
            Payment::create([
                'reservation_id' => $reservation->id,
                'shift_id'       => $shift->id,
                'received_by'    => $r['created_by'],
                'amount'         => $paid,
                'currency'       => 'YER',
                'method'         => $r['pay_method'] ?? 'cash',
                'payment_date'   => $checkIn->copy()->addHour(),
                'type'           => 'room',
            ]);
        }

        // Companions
        foreach ($r['companions'] ?? [] as $c) {
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

    private function seedReservations(array $guests, array $shifts, int $adminId, int $receptionId, int $accountantId): void
    {
        // ─── حجوزات مسجّل خروج (تاريخية) ────────────────────────────────────
        $checkedOut = [
            [
                'guest_idx'  => 0, 'room' => '202',
                'check_in'   => now()->subDays(10)->toDateString(),
                'check_out'  => now()->subDays(7)->toDateString(),
                'actual_checkout' => now()->subDays(7)->setHour(12)->toDateTimeString(),
                'price' => 15000, 'paid' => 45000, 'pay_method' => 'cash',
                'created_by' => $receptionId, 'status' => 'checked_out',
                'purpose' => 'علاج', 'origin' => 'تعز',
                'companions' => [
                    ['full_name' => 'فاطمة أحمد الشرجبي', 'nationality' => 'يمني', 'id_type' => 'national_id', 'id_number' => '1501234560', 'relationship' => 'wife'],
                ],
            ],
            [
                'guest_idx'  => 1, 'room' => '303',
                'check_in'   => now()->subDays(8)->toDateString(),
                'check_out'  => now()->subDays(5)->toDateString(),
                'actual_checkout' => now()->subDays(5)->setHour(11)->toDateTimeString(),
                'price' => 15000, 'paid' => 45000, 'pay_method' => 'cash',
                'created_by' => $receptionId, 'status' => 'checked_out',
                'purpose' => 'سياحة', 'origin' => 'عدن',
            ],
            [
                'guest_idx'  => 2, 'room' => '401A', 'linked_room' => '401B', 'suite_booking_type' => 'both',
                'check_in'   => now()->subDays(5)->toDateString(),
                'check_out'  => now()->subDays(2)->toDateString(),
                'actual_checkout' => now()->subDays(2)->setHour(12)->toDateTimeString(),
                'price' => 50000, 'paid' => 150000, 'pay_method' => 'bank_transfer',
                'created_by' => $adminId, 'status' => 'checked_out',
                'purpose' => 'سياحة', 'origin' => 'الرياض',
                'companions' => [
                    ['full_name' => 'نورة خالد النجار',        'nationality' => 'سعودي', 'id_type' => 'national_id', 'id_number' => '2054321090', 'relationship' => 'wife'],
                    ['full_name' => 'عبدالله عبدالرحمن النجار', 'nationality' => 'سعودي', 'id_type' => 'national_id', 'id_number' => '3054321090', 'relationship' => 'son'],
                    ['full_name' => 'سارة عبدالرحمن النجار',   'nationality' => 'سعودي', 'id_type' => 'national_id', 'id_number' => '4054321090', 'relationship' => 'daughter'],
                ],
            ],
            [
                'guest_idx'  => 7, 'room' => '504',
                'check_in'   => now()->subDays(4)->toDateString(),
                'check_out'  => now()->subDays(1)->toDateString(),
                'actual_checkout' => now()->subDays(1)->setHour(10)->toDateTimeString(),
                'price' => 15000, 'paid' => 30000, 'pay_method' => 'cash',
                'created_by' => $receptionId, 'status' => 'checked_out',
                'purpose' => 'عمل', 'origin' => 'إب',
            ],
        ];

        foreach ($checkedOut as $r) {
            $this->makeReservation($r, $guests, $shifts);
        }

        // ─── حجوزات مسجّل دخول حالياً ────────────────────────────────────────
        $checkedIn = [
            [
                'guest_idx'  => 3, 'room' => '203',
                'check_in'   => now()->subDays(2)->toDateString(),
                'check_out'  => now()->addDays(3)->toDateString(),
                'price' => 15000, 'paid' => 30000, 'pay_method' => 'cash',
                'created_by' => $receptionId, 'status' => 'checked_in',
                'purpose' => 'علاج', 'origin' => 'حجة',
            ],
            [
                'guest_idx'  => 4, 'room' => '301A', 'linked_room' => '301B', 'suite_booking_type' => 'both',
                'check_in'   => now()->subDays(1)->toDateString(),
                'check_out'  => now()->addDays(4)->toDateString(),
                'price' => 50000, 'paid' => 50000, 'pay_method' => 'pos',
                'created_by' => $adminId, 'status' => 'checked_in',
                'purpose' => 'سياحة', 'origin' => 'جدة',
                'companions' => [
                    ['full_name' => 'منيرة فيصل الغامدي', 'nationality' => 'سعودي', 'id_type' => 'national_id', 'id_number' => '2076543210', 'relationship' => 'wife'],
                ],
            ],
            [
                'guest_idx'  => 5, 'room' => '402',
                'check_in'   => now()->toDateString(),
                'check_out'  => now()->addDays(2)->toDateString(),
                'price' => 15000, 'paid' => 15000, 'pay_method' => 'cash',
                'created_by' => $receptionId, 'status' => 'checked_in',
                'purpose' => 'عمل', 'origin' => 'موغاديشو',
            ],
            [
                'guest_idx'  => 6, 'room' => '502',
                'check_in'   => now()->toDateString(),
                'check_out'  => now()->addDays(5)->toDateString(),
                'price' => 15000, 'paid' => 75000, 'pay_method' => 'cash',
                'created_by' => $receptionId, 'status' => 'checked_in',
                'purpose' => 'سياحة', 'origin' => 'تعز',
                'companions' => [
                    ['full_name' => 'أمل علي المقطري',   'nationality' => 'يمني', 'id_type' => 'national_id', 'id_number' => '1507654320', 'relationship' => 'wife'],
                    ['full_name' => 'أحمد علي المقطري',  'nationality' => 'يمني', 'id_type' => 'national_id', 'id_number' => '1607654320', 'relationship' => 'son'],
                ],
            ],
            [
                'guest_idx'  => 9, 'room' => '602',
                'check_in'   => now()->subDays(1)->toDateString(),
                'check_out'  => now()->addDays(6)->toDateString(),
                'price' => 15000, 'paid' => 15000, 'pay_method' => 'bank_transfer',
                'created_by' => $accountantId, 'status' => 'checked_in',
                'purpose' => 'عمل', 'origin' => 'القاهرة',
            ],
        ];

        foreach ($checkedIn as $r) {
            $this->makeReservation($r, $guests, $shifts);
        }

        // ─── حجوزات مؤكدة مستقبلية ────────────────────────────────────────────
        $confirmed = [
            [
                'guest_idx'  => 8, 'room' => '304',
                'check_in'   => now()->addDays(2)->toDateString(),
                'check_out'  => now()->addDays(5)->toDateString(),
                'price' => 15000, 'paid' => 0,
                'created_by' => $adminId, 'status' => 'confirmed',
                'purpose' => 'سياحة', 'origin' => 'عمّان',
            ],
            [
                'guest_idx'  => 10, 'room' => '403',
                'check_in'   => now()->addDays(3)->toDateString(),
                'check_out'  => now()->addDays(7)->toDateString(),
                'price' => 15000, 'paid' => 30000, 'pay_method' => 'cash',
                'created_by' => $receptionId, 'status' => 'confirmed',
                'purpose' => 'دراسة', 'origin' => 'جدة',
            ],
            [
                'guest_idx'  => 11, 'room' => '603',
                'check_in'   => now()->addDays(1)->toDateString(),
                'check_out'  => now()->addDays(4)->toDateString(),
                'price' => 15000, 'paid' => 0,
                'created_by' => $receptionId, 'status' => 'confirmed',
                'purpose' => 'عمل', 'origin' => 'صنعاء',
            ],
        ];

        foreach ($confirmed as $r) {
            $this->makeReservation($r, $guests, $shifts);
        }

        // ─── حجز ملغي ─────────────────────────────────────────────────────────
        $cancelledRoom = $this->room('204');
        if ($cancelledRoom) {
            Reservation::create([
                'guest_id'       => $guests[7]->id,
                'room_id'        => $cancelledRoom->id,
                'created_by'     => $receptionId,
                'check_in_date'  => now()->subDays(2)->toDateString(),
                'check_out_date' => now()->addDay()->toDateString(),
                'origin'         => 'إب',
                'purpose'        => 'علاج',
                'status'         => 'cancelled',
                'payment_status' => 'unpaid',
                'total_amount'   => 45000,
                'paid_amount'    => 0,
            ]);
        }

        // ─── غرف تحت الصيانة / الفحص ─────────────────────────────────────────
        $this->room('206')?->update(['status' => 'maintenance']);
        $this->room('306')?->update(['status' => 'under_inspection']);

        // ─── تحديث إجماليات الورديات ──────────────────────────────────────────
        foreach ($shifts as $shift) {
            $total = Payment::where('shift_id', $shift->id)->sum('amount');
            if ($total > 0) {
                $shift->update(['total_received_yer' => $total]);
            }
        }
    }
}
