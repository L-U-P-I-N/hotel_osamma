<?php
namespace App\Http\Controllers;

use App\Helpers\StorageHelper;
use App\Models\Companion;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\Shift;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\RefundService;
use App\Services\ShiftService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReservationController extends Controller
{
    public function index(Request $request)
    {
        $query = Reservation::with(['guest', 'room.roomType', 'createdBy']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('guest', fn($g) => $g->where('full_name', 'like', "%{$search}%"))
                  ->orWhereHas('room', fn($r) => $r->where('room_number', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('from')) {
            $query->whereDate('check_in_date', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('check_out_date', '<=', $request->to);
        }

        if ($request->filled('created_by')) {
            $query->where('created_by', $request->created_by);
        }

        // من لم يغادروا أولاً، ثم من غادروا في النهاية — وداخل كل مجموعة الأحدث أولاً
        $reservations = $query->orderByRaw("CASE WHEN status = 'checked_in' THEN 0 ELSE 1 END")
            ->latest()
            ->paginate(25)
            ->withQueryString();
        $staff = User::where('is_active', true)->orderBy('name')->get();

        return view('reservations.index', compact('reservations', 'staff'));
    }

    public function show(Reservation $reservation)
    {
        // احتساب التجديد التلقائي (إن كان مفعّلاً) قبل عرض التفاصيل — لا مجدول زمني
        // في بيئة التشغيل، فنُعوّض الأيام المتأخرة انتهازياً عند فتح الصفحة.
        $reservation->applyAutoRenewCatchUp();

        $reservation->load(['guest', 'room.roomType', 'companions', 'payments', 'extraCharges', 'roomInspections.images', 'createdBy', 'adminApproval']);
        $availableRooms = $reservation->status === 'checked_in'
            ? Room::with('roomType')->where('status', 'available')->orderBy('floor')->orderBy('room_number')->get()
            : collect();
        $transferOptions = $reservation->status === 'checked_in'
            ? $this->buildTransferOptions($reservation, $availableRooms)
            : collect();

        // قيد التجديد: موعد وصول أقرب نزيل قادم على نفس الغرفة، وأقصى تاريخ خروج
        // مسموح به = ذلك الموعد ناقص يوم فاصل للتنظيف. كلاهما null إن لا يوجد قادم.
        $renewNextArrival = $reservation->status === 'checked_in'
            ? Reservation::nextCheckInAfter($reservation->occupiedRoomIds(), $reservation->check_in_date, $reservation->id)
            : null;
        $renewMaxCheckout = $renewNextArrival?->copy()->subDays(Reservation::TURNOVER_BUFFER_DAYS);

        return view('reservations.show', compact('reservation', 'availableRooms', 'transferOptions', 'renewMaxCheckout', 'renewNextArrival'));
    }

    /**
     * صفحة مستقلة لعرض بيانات مرافقي الحجز — يُفتَح إليها من زر "المرافقون" في
     * صفحة تفاصيل الحجز لإبقاء تلك الصفحة مضغوطة بلا تمرير.
     */
    public function companions(Reservation $reservation)
    {
        $reservation->load(['guest', 'companions']);

        return view('reservations.companions', compact('reservation'));
    }

    /**
     * خيارات النقل: كل غرفة/قسم متاح منفرداً، بالإضافة إلى خيار "جناح كامل A+B"
     * عندما يكون القسمان متاحين — مع سعر الليلة لكل خيار لإعادة احتساب الإجمالي.
     */
    private function buildTransferOptions(Reservation $reservation, $availableRooms)
    {
        $options = collect();

        // الأقسام والغرف المتاحة منفردة
        foreach ($availableRooms as $room) {
            $label = ($room->isSuite() ? 'جناح ' : 'غرفة ') . $room->room_number
                . ' — الطابق ' . $room->floor
                . ($room->roomType ? ' (' . $room->roomType->name . ')' : '');
            $options->push([
                'value' => (string) $room->id,
                'label' => $label,
                'price' => $room->priceFor('YER'),
            ]);
        }

        // الأجنحة الكاملة A+B: قسم A متاح وقسمه المقابل متاح أيضاً
        foreach ($availableRooms as $room) {
            if (!$room->isSuiteA()) {
                continue;
            }
            $partner = $room->suitePartner();
            if (!$partner || $partner->status !== 'available') {
                continue;
            }
            $options->push([
                'value' => $room->id . ':both',
                'label' => 'جناح كامل ' . rtrim($room->room_number, 'ABab') . ' (A+B) — الطابق ' . $room->floor,
                'price' => $room->fullSuitePrice(),
            ]);
        }

        // ترقية في نفس المكان: النزيل في قسم جناح وقسمه المقابل متاح → جناح كامل
        $current = $reservation->room;
        if ($current && $current->isSuite() && $reservation->suite_booking_type !== 'both') {
            $partner = $current->suitePartner();
            if ($partner && $partner->status === 'available') {
                $options->push([
                    'value' => $current->id . ':both',
                    'label' => 'ترقية للجناح الكامل ' . rtrim($current->room_number, 'ABab') . ' (A+B) — نفس الموقع',
                    'price' => $current->fullSuitePrice(),
                ]);
            }
        }

        return $options->unique('value')->values();
    }

    public function edit(Reservation $reservation)
    {
        if (!in_array($reservation->status, ['confirmed', 'checked_in'])) {
            return back()->with('error', 'لا يمكن تعديل هذا الحجز في حالته الحالية');
        }

        $reservation->load(['guest', 'room.roomType', 'companions']);

        $companionsData = $reservation->companions->map(fn($c) => [
            'id'             => $c->id,
            'full_name'      => $c->full_name,
            'nationality'    => $c->nationality ?? '',
            'id_type'        => $c->id_type ?? '',
            'id_number'      => $c->id_number ?? '',
            'id_issuer'      => $c->id_issuer ?? '',
            'id_issue_date'  => $c->id_issue_date?->format('Y-m-d') ?? '',
            'relationship'   => $c->relationship ?? '',
            'has_image'      => (bool) $c->id_image_path,
            'has_marriage'   => (bool) $c->marriage_doc_path,
            'delete'         => false,
            '_key'           => $c->id,
        ])->values()->toArray();

        $nights = ($reservation->check_in_date && $reservation->check_out_date)
            ? $reservation->check_in_date->diffInDays($reservation->check_out_date)
            : 0;
        // سعر الليلة من إجمالي الغرفة قبل الخصم (بدون الرسوم الإضافية) حتى لا
        // تُحتسب مشتريات/أضرار النزيل ضمن سعر الليلة في نموذج التعديل
        $currentPricePerNight = $nights > 0
            ? round($reservation->gross_total / $nights, 2)
            : ($reservation->room?->roomType?->base_price ?? 0);

        return view('reservations.edit', compact('reservation', 'companionsData', 'currentPricePerNight'));
    }

    public function update(Request $request, Reservation $reservation)
    {
        if (!in_array($reservation->status, ['confirmed', 'checked_in'])) {
            return back()->with('error', 'لا يمكن تعديل هذا الحجز في حالته الحالية');
        }

        $validated = $request->validate([
            'check_in_date'                 => 'required|date',
            'check_out_date'                => 'required|date|after_or_equal:check_in_date',
            'purpose'                       => 'nullable|string|max:255',
            'origin'                        => 'nullable|string|max:255',
            'notes'                         => 'nullable|string|max:1000',
            // Guest fields
            'guest_full_name'               => 'required|string|max:255',
            'guest_nationality'             => 'nullable|string|max:100',
            'guest_occupation'              => 'nullable|string|max:100',
            'guest_id_type'                 => 'nullable|string|max:50',
            'guest_id_number'               => 'nullable|string|max:50',
            'guest_id_issuer'               => 'nullable|string|max:100',
            'guest_id_issue_date'           => 'nullable|date',
            'guest_phone'                   => 'nullable|string|max:30',
            'guest_id_image'                => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            // Companions
            'companions'                    => 'nullable|array',
            'companions.*.id'               => 'nullable|integer',
            'companions.*.full_name'        => 'nullable|string|max:255',
            'companions.*.nationality'      => 'nullable|string|max:100',
            'companions.*.id_type'          => 'nullable|string|max:50',
            'companions.*.id_number'        => 'nullable|string|max:50',
            'companions.*.id_issuer'        => 'nullable|string|max:100',
            'companions.*.id_issue_date'    => 'nullable|date',
            'companions.*.relationship'     => 'nullable|string|max:50',
            'companions.*.delete'           => 'nullable|boolean',
            'companions.*.id_image'         => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'companions.*.marriage_doc'     => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'price_per_night'               => 'nullable|numeric|min:0',
            'renewal_price_per_night'       => 'nullable|numeric|min:0',
        ], [
            'check_in_date.required'     => 'تاريخ الدخول مطلوب',
            'check_in_date.date'         => 'تاريخ الدخول غير صالح',
            'check_out_date.required'    => 'تاريخ الخروج مطلوب',
            'check_out_date.date'        => 'تاريخ الخروج غير صالح',
            'check_out_date.after_or_equal' => 'تاريخ الخروج لا يمكن أن يكون قبل تاريخ الدخول',
            'guest_full_name.required'   => 'اسم النزيل مطلوب',
        ]);

        $old = $reservation->toArray();

        try {
            // Update guest
            if ($reservation->guest) {
                $guestData = [
                    'full_name'     => $validated['guest_full_name'],
                    'nationality'   => $this->nullIfEmpty($validated['guest_nationality'] ?? null),
                    'occupation'    => $this->nullIfEmpty($validated['guest_occupation'] ?? null),
                    'id_type'       => $this->nullIfEmpty($validated['guest_id_type'] ?? null),
                    'id_number'     => $this->nullIfEmpty($validated['guest_id_number'] ?? null),
                    'id_issuer'     => $this->nullIfEmpty($validated['guest_id_issuer'] ?? null),
                    'id_issue_date' => $this->nullIfEmpty($validated['guest_id_issue_date'] ?? null),
                    'phone'         => $this->nullIfEmpty($validated['guest_phone'] ?? null),
                ];

                // Replace the guest ID image only when a new file is uploaded
                if ($request->hasFile('guest_id_image')) {
                    $guestData['id_image_path'] = StorageHelper::store($request->file('guest_id_image'), 'id_images/guests');
                }

                $reservation->guest->update($guestData);
            }

            // Update companions
            $compFiles = $request->file('companions', []);
            foreach ($request->input('companions', []) as $idx => $comp) {
                if (($comp['delete'] ?? '0') === '1' && !empty($comp['id'])) {
                    Companion::where('id', $comp['id'])->where('reservation_id', $reservation->id)->delete();
                    continue;
                }
                if (empty($comp['full_name'])) continue;

                // id_type و relationship عمودان enum غير قابلين للـ null (لهما قيم
                // افتراضية) — لا نمرّر null وإلا فشل الإدراج عند إضافة مرافق جديد
                // في التعديل، فيرتد النموذج ويضيع ما أُدخِل.
                $data = [
                    'full_name'      => $comp['full_name'],
                    'nationality'    => $this->nullIfEmpty($comp['nationality'] ?? null),
                    'id_type'        => $this->nullIfEmpty($comp['id_type'] ?? null) ?: 'national_id',
                    'id_number'      => $this->nullIfEmpty($comp['id_number'] ?? null),
                    'id_issuer'      => $this->nullIfEmpty($comp['id_issuer'] ?? null),
                    'id_issue_date'  => $this->nullIfEmpty($comp['id_issue_date'] ?? null),
                    'relationship'   => $this->nullIfEmpty($comp['relationship'] ?? null) ?: 'other',
                ];

                // Handle ID image upload (replace only when a new file is provided)
                if (!empty($compFiles[$idx]['id_image'])) {
                    $data['id_image_path'] = StorageHelper::store($compFiles[$idx]['id_image'], 'id_images/companions');
                }

                // Handle marriage doc upload (for wife)
                if (($comp['relationship'] ?? '') === 'wife' && !empty($compFiles[$idx]['marriage_doc'])) {
                    $data['marriage_doc_path'] = StorageHelper::store($compFiles[$idx]['marriage_doc'], 'marriage_docs');
                }

                if (!empty($comp['id'])) {
                    $existing = Companion::where('id', $comp['id'])->where('reservation_id', $reservation->id)->first();
                    $existing?->update($data);
                } else {
                    $reservation->companions()->create(array_merge($data, ['reservation_id' => $reservation->id]));
                }
            }

            $nights = Carbon::parse($validated['check_in_date'])->diffInDays($validated['check_out_date']);
            $billableNights = max($nights, 1); // المبيت في نفس اليوم يُحتسب ليلة واحدة كحد أدنى
            $submittedPrice = isset($validated['price_per_night']) && $validated['price_per_night'] > 0
                ? (float) $validated['price_per_night']
                : null;
            $pricePerNight = $submittedPrice ?? $reservation->room?->roomType?->base_price ?? $reservation->gross_total / $billableNights;
            // إجمالي الغرفة قبل الخصم ثم نطبّق الخصم، ثم نُعيد الرسوم الإضافية حتى
            // لا يضيع الخصم ولا تُفقد مصروفات النزيل عند تعديل التاريخ/السعر
            $grossTotal = $billableNights * $pricePerNight;
            $discountAmount = $reservation->discountAmountFor($grossTotal);
            $newTotal = round(max(0, round($grossTotal - $discountAmount, 2)) + $reservation->extra_charges_total, 0);

            $reservation->update([
                'check_in_date'  => $validated['check_in_date'],
                'check_out_date' => $validated['check_out_date'],
                'purpose'        => $this->nullIfEmpty($validated['purpose'] ?? null),
                'origin'         => $this->nullIfEmpty($validated['origin'] ?? null),
                'notes'          => $this->nullIfEmpty($validated['notes'] ?? null),
                'discount_amount'=> $discountAmount,
                'total_amount'   => $newTotal,
                'renewal_price_per_night' => isset($validated['renewal_price_per_night']) && $validated['renewal_price_per_night'] !== ''
                    ? $validated['renewal_price_per_night'] : null,
            ]);

            // إن أصبح الإجمالي الجديد أقل من المبلغ المسجَّل كمدفوع (تصحيح خطأ في السعر)،
            // نُخفّض الدفعات المسجَّلة لتطابق الإجمالي الجديد ونُعيد حساب الورديات المتأثرة،
            // وإلا ستبقى مستلمات الوردية محسوبة بالسعر القديم الخاطئ.
            $this->reconcileOverpayment($reservation);

            AuditLogService::log('update', $reservation, $old, $reservation->fresh()->toArray(), auth()->user());

        } catch (\Illuminate\Database\QueryException $e) {
            // Map DB constraint errors to friendly field-level messages
            $msg = $e->getMessage();
            $fieldMap = [
                'guests.id_number'   => ['guest_id_number',  'رقم الهوية لا يمكن أن يكون فارغاً'],
                'guests.phone'       => ['guest_phone',       'رقم الهاتف لا يمكن أن يكون فارغاً'],
                'guests.full_name'   => ['guest_full_name',   'الاسم الكامل لا يمكن أن يكون فارغاً'],
                'companions.id_number' => ['companions.0.id_number', 'رقم هوية المرافق لا يمكن أن يكون فارغاً'],
            ];
            foreach ($fieldMap as $column => [$field, $label]) {
                if (str_contains($msg, $column)) {
                    return back()->withInput()->withErrors([$field => $label]);
                }
            }
            return back()->withInput()->withErrors(['general' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()]);
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['general' => 'حدث خطأ غير متوقع: ' . $e->getMessage()]);
        }

        return redirect()->route('reservations.show', $reservation)->with('success', 'تم تحديث الحجز بنجاح');
    }

    public function renew(Request $request, Reservation $reservation)
    {
        if ($reservation->status !== 'checked_in') {
            return back()->withErrors(['error' => 'لا يمكن تجديد إلا الحجوزات النشطة (مسجل دخول)']);
        }

        $validated = $request->validate([
            'new_check_out_date'      => 'required|date|after:' . $reservation->check_out_date->format('Y-m-d'),
            'renewal_price_per_night' => 'nullable|numeric|min:0',
            'advance_payment'         => 'nullable|numeric|min:0',
            'payment_method'          => 'nullable|in:cash,pos,bank_transfer',
            'notes'                   => 'nullable|string|max:500',
            'payment_notes'           => 'nullable|string|max:500',
        ], [
            'new_check_out_date.required' => 'تاريخ الخروج الجديد مطلوب',
            'new_check_out_date.after'    => 'يجب أن يكون تاريخ الخروج الجديد بعد التاريخ الحالي',
        ]);

        // منع التجديد فوق حجزٍ قادم على نفس الغرفة/الجناح مع ترك يوم فاصل للتنظيف:
        // يجب أن ينتهي التمديد قبل وصول أقرب نزيل قادم بيوم على الأقل.
        $conflict = Reservation::findOverlap(
            $reservation->occupiedRoomIds(),
            $reservation->check_in_date,
            $validated['new_check_out_date'],
            $reservation->id
        );
        if ($conflict) {
            $arrival     = Carbon::parse($conflict->check_in_date)->format('Y/m/d');
            $maxCheckout = Carbon::parse($conflict->check_in_date)
                ->subDays(Reservation::TURNOVER_BUFFER_DAYS)->format('Y/m/d');
            return back()->withErrors(['new_check_out_date' =>
                'الغرفة محجوزة لنزيل قادم (' . ($conflict->guest?->full_name ?? '—') . ') يصل بتاريخ '
                . $arrival . '، ويجب ترك يوم فاصل للتنظيف قبل وصوله. أقصى تاريخ خروج متاح: '
                . $maxCheckout
            ])->withInput();
        }

        // عدد الليالي الإضافية = الفرق بين تاريخ الخروج الحالي والجديد (بالأيام
        // الكاملة). نحسبه من بداية اليوم في كلا الطرفين حتى لا يتأثر بأي وقت
        // ضمني أو باختلاف سلوك diffInDays بين إصدارات Carbon.
        $currentOut  = $reservation->check_out_date->copy()->startOfDay();
        $newOut      = Carbon::parse($validated['new_check_out_date'])->startOfDay();
        $extraNights = (int) $currentOut->diffInDays($newOut);

        // سعر ليلة التجديد قابل للتعديل من الموظف؛ إن تُرك فارغاً نستخدم السعر
        // الافتراضي المحفوظ للحجز. السعر المُستخدَم يُحفَظ كافتراضي لأي تجديد لاحق.
        $pricePerNight = ($validated['renewal_price_per_night'] ?? '') !== ''
            ? (float) $validated['renewal_price_per_night']
            : $reservation->effective_renewal_price_per_night;
        $extraAmount   = $extraNights * $pricePerNight;

        // نعيد بناء الإجمالي قبل الخصم (الصافي + الخصم) ثم نضيف ليالي التجديد
        // ونطبّق الخصم المحفوظ على الإجمالي الجديد حتى يبقى الخصم ساري المفعول.
        $newGross       = $reservation->gross_total + $extraAmount;
        $discountAmount = $reservation->discountAmountFor($newGross);
        // نقرّب الإجمالي لأقرب ريال (عملة صحيحة عملياً) لتفادي تراكم كسور القسمة
        $newTotal       = round(max(0, round($newGross - $discountAmount, 2)) + $reservation->extra_charges_total, 0);

        $old = $reservation->only(['check_out_date', 'total_amount', 'renewal_price_per_night']);

        // نُلحق ملاحظة المستخدم دائماً بعلامة التجديد (كانت تُهمل سابقاً إن وُجدت ملاحظات قديمة)
        $renewalNote = "[تجديد +{$extraNights} ليلة بسعر " . number_format($pricePerNight, 0) . " ر.ي/ليلة]"
            . (!empty($validated['notes']) ? ': ' . $validated['notes'] : '');

        $reservation->update([
            'check_out_date'          => $validated['new_check_out_date'],
            'total_amount'            => $newTotal,
            'discount_amount'         => $discountAmount,
            'renewal_price_per_night' => $pricePerNight,
            'notes'                   => $reservation->notes
                                    ? $reservation->notes . "\n" . $renewalNote
                                    : $renewalNote,
        ]);

        if (!empty($validated['advance_payment']) && $validated['advance_payment'] > 0) {
            // ربط دفعة التجديد بالوردية المفتوحة للموظف الحالي حتى تظهر عند إقفالها
            $shiftService = app(\App\Services\ShiftService::class);
            $shift = $shiftService->getActiveShift(auth()->user());

            \App\Models\Payment::create([
                'reservation_id' => $reservation->id,
                'shift_id'       => $shift?->id,
                'received_by'    => auth()->id(),
                'amount'         => $validated['advance_payment'],
                'currency'       => 'YER',
                'method'         => $validated['payment_method'] ?? 'cash',
                'payment_date'   => now(),
                'type'           => 'renewal',
                'notes'          => $validated['payment_notes'] ?? null,
            ]);
            $reservation->increment('paid_amount', $validated['advance_payment']);
            $reservation->refresh()->updatePaymentStatus();

            if ($shift) {
                $shiftService->computeTotals($shift);
            }
        }

        AuditLogService::log('update', $reservation, $old, [
            'check_out_date'  => $validated['new_check_out_date'],
            'extra_nights'    => $extraNights,
            'extra_amount'    => $extraAmount,
            'price_per_night' => $pricePerNight,
        ], auth()->user());

        return redirect()->route('reservations.show', $reservation)
            ->with('success', "تم تجديد الإقامة بنجاح — تمديد {$extraNights} ليلة إضافية");
    }

    /**
     * تفعيل/إيقاف التجديد التلقائي للإقامة. عند التفعيل يُعوَّض فوراً أي يومٍ مضى
     * تجاوز الساعة 1 ظهراً من تاريخ الخروج الحالي.
     */
    public function toggleAutoRenew(Reservation $reservation)
    {
        if ($reservation->status !== 'checked_in') {
            return back()->withErrors(['error' => 'التجديد التلقائي متاح فقط للحجوزات النشطة (مسجل دخول)']);
        }

        $reservation->update(['auto_renew' => !$reservation->auto_renew]);

        $added = 0;
        if ($reservation->auto_renew) {
            $added = $reservation->applyAutoRenewCatchUp();
        }

        AuditLogService::log('update', $reservation, null, [
            'auto_renew' => $reservation->auto_renew,
        ], auth()->user());

        $msg = $reservation->auto_renew
            ? 'تم تفعيل التجديد التلقائي — سيُحتسب يوم جديد تلقائياً بعد كل ساعة 1 ظهراً'
              . ($added > 0 ? " (أُضيفت {$added} ليلة عن الأيام السابقة)" : '')
            : 'تم إيقاف التجديد التلقائي';

        return redirect()->route('reservations.show', $reservation)->with('success', $msg);
    }

    /**
     * تعديل تاريخ وصول النزيل فقط (بدون المساس بالمبلغ أو المدفوعات) — لحالة
     * نزيل دفع مسبقاً لكنه أبلغ بأن وصوله الفعلي سيتأخر عدة أيام. نُحرِّك
     * تاريخ الخروج بنفس عدد الأيام حتى تبقى مدة الإقامة والإجمالي كما هما،
     * بخلاف نموذج تعديل الحجز العام الذي يعيد حساب الإجمالي من عدد الليالي
     * وقد يُنقص المدفوعات المسجَّلة (reconcileOverpayment) إن قصُرت المدة.
     */
    public function updateCheckInDate(Request $request, Reservation $reservation)
    {
        if ($reservation->status !== 'checked_in') {
            return back()->withErrors(['error' => 'لا يمكن تعديل تاريخ الوصول إلا للحجوزات النشطة (مسجل دخول)']);
        }

        $validated = $request->validate([
            'new_check_in_date' => 'required|date',
        ], [
            'new_check_in_date.required' => 'تاريخ الوصول الجديد مطلوب',
        ]);

        $oldCheckIn  = $reservation->check_in_date->copy()->startOfDay();
        $newCheckIn  = Carbon::parse($validated['new_check_in_date'])->startOfDay();
        $deltaDays   = (int) round(($newCheckIn->getTimestamp() - $oldCheckIn->getTimestamp()) / 86400);
        $newCheckOut = $reservation->check_out_date->copy()->addDays($deltaDays);

        $old = $reservation->only(['check_in_date', 'check_out_date']);

        $reservation->update([
            'check_in_date'  => $newCheckIn->toDateString(),
            'check_out_date' => $newCheckOut->toDateString(),
        ]);

        AuditLogService::log('update', $reservation, $old, [
            'check_in_date'  => $newCheckIn->toDateString(),
            'check_out_date' => $newCheckOut->toDateString(),
        ], auth()->user());

        return redirect()->route('reservations.show', $reservation)
            ->with('success', 'تم تعديل تاريخ الوصول بنجاح');
    }

    public function transferRoom(Request $request, Reservation $reservation)
    {
        if ($reservation->status !== 'checked_in') {
            return back()->with('error', 'تغيير الغرفة متاح فقط للحجوزات النشطة (مسجل دخول)');
        }

        // القيمة إما رقم غرفة "12" أو جناح كامل "12:both"
        $validated = $request->validate([
            'new_room_selection' => ['required', 'string', 'regex:/^\d+(:both)?$/'],
            'notes'              => 'nullable|string|max:500',
        ], [
            'new_room_selection.required' => 'يرجى اختيار الغرفة الجديدة',
            'new_room_selection.regex'    => 'اختيار الغرفة غير صالح',
        ]);

        [$newRoomId, $scope] = array_pad(explode(':', $validated['new_room_selection']), 2, null);
        $wantsFullSuite = $scope === 'both';

        $newRoom = Room::with('roomType')->find($newRoomId);
        if (!$newRoom) {
            return back()->withErrors(['new_room_selection' => 'الغرفة المختارة غير موجودة']);
        }

        $wasFullSuite = $reservation->suite_booking_type === 'both';
        if ($newRoom->id === $reservation->room_id && $wantsFullSuite === $wasFullSuite) {
            return back()->withErrors(['new_room_selection' => 'الغرفة الجديدة يجب أن تختلف عن الغرفة الحالية']);
        }

        // القسم المقابل عند اختيار جناح كامل
        $partner = null;
        if ($wantsFullSuite) {
            if (!$newRoom->isSuite()) {
                return back()->withErrors(['new_room_selection' => 'الغرفة المختارة ليست جناحاً']);
            }
            $partner = $newRoom->suitePartner();
            if (!$partner) {
                return back()->withErrors(['new_room_selection' => 'تعذّر العثور على القسم المقابل للجناح ' . $newRoom->room_number]);
            }
        }

        // التحقق من الإتاحة — يُسمح بالغرف المشغولة بهذا الحجز نفسه (ترقية في نفس المكان)
        $currentIds = array_filter([$reservation->room_id, $reservation->linked_room_id]);
        foreach (array_filter([$newRoom, $partner]) as $target) {
            if ($target->status !== 'available' && !in_array($target->id, $currentIds)) {
                return back()->withErrors(['new_room_selection' => 'الغرفة ' . $target->room_number . ' غير متاحة']);
            }
        }

        // ── إعادة احتساب الإجمالي: الليالي الماضية بالسعر القديم + المتبقية بسعر الغرفة/الجناح الجديد ──
        $nights = max(1, $reservation->nights);
        $stayedNights = (int) min(max($reservation->check_in_date->diffInDays(today(), false), 0), $nights);
        $remainingNights = $nights - $stayedNights;

        $oldPricePerNight = round((float) $reservation->gross_total / $nights, 2);
        $newPricePerNight = $wantsFullSuite ? $newRoom->fullSuitePrice() : $newRoom->priceFor('YER');
        if ($newPricePerNight <= 0) {
            // لا سعر معرّف للغرفة الجديدة → نُبقي السعر الحالي دون تغيير
            $newPricePerNight = $oldPricePerNight;
        }
        // إجمالي الغرفة قبل الخصم ثم الخصم ثم إعادة الرسوم الإضافية (تفادي فقدها)
        $grossTotal     = round($stayedNights * $oldPricePerNight + $remainingNights * $newPricePerNight, 2);
        $discountAmount = $reservation->discountAmountFor($grossTotal);
        $newTotal       = round(max(0, round($grossTotal - $discountAmount, 2)) + $reservation->extra_charges_total, 0);

        $oldRoom       = $reservation->room;
        $oldLinkedRoom = $reservation->linkedRoom;
        $oldLabel      = ($wasFullSuite ? 'جناح كامل ' : 'غرفة ') . $reservation->display_room_number;
        $newLabel      = $wantsFullSuite
            ? 'جناح كامل ' . rtrim($newRoom->room_number, 'ABab') . ' (A+B)'
            : 'غرفة ' . $newRoom->room_number;

        $old = $reservation->only(['room_id', 'linked_room_id', 'suite_booking_type', 'total_amount']);

        // نُلحق سبب النقل دائماً بعلامة النقل (كان يُهمل سابقاً إن وُجدت ملاحظات قديمة)
        $transferNote = "[نقل من {$oldLabel} إلى {$newLabel}]"
            . (!empty($validated['notes']) ? ': ' . $validated['notes'] : '');
        if (round($newTotal - (float) $reservation->total_amount, 2) != 0.0) {
            $transferNote .= ' — تعديل الإجمالي من ' . number_format((float) $reservation->total_amount, 0)
                . ' إلى ' . number_format($newTotal, 0) . ' ر.ي'
                . " ({$remainingNights} ليلة متبقية × " . number_format($newPricePerNight, 0) . ')';
        }

        // نوع حجز الجناح بعد النقل
        $suiteBookingType = $wantsFullSuite
            ? 'both'
            : ($newRoom->isSuiteA() ? 'a_only' : ($newRoom->isSuiteB() ? 'b_only' : null));

        // Move guest to new room(s)
        $reservation->update([
            'room_id'            => $newRoom->id,
            'linked_room_id'     => $partner?->id,
            'suite_booking_type' => $suiteBookingType,
            'discount_amount'    => $discountAmount,
            'total_amount'       => $newTotal,
            'notes'              => $reservation->notes
                                        ? $reservation->notes . "\n" . $transferNote
                                        : $transferNote,
        ]);
        $reservation->refresh()->updatePaymentStatus();

        // الغرف الجديدة تصبح مشغولة، والقديمة (غير المشمولة بالحجز الجديد) تذهب للفحص
        $newIds = array_filter([$newRoom->id, $partner?->id]);
        foreach (array_filter([$oldRoom, $oldLinkedRoom]) as $released) {
            if (!in_array($released->id, $newIds)) {
                $released->update(['status' => 'under_inspection']);
            }
        }
        $newRoom->update(['status' => 'occupied']);
        $partner?->update(['status' => 'occupied']);

        AuditLogService::log('update', $reservation, $old, [
            'room_id'            => $newRoom->id,
            'linked_room_id'     => $partner?->id,
            'suite_booking_type' => $suiteBookingType,
            'total_amount'       => $newTotal,
            'action'             => 'room_transfer',
        ], auth()->user());

        return redirect()->route('reservations.show', $reservation)
            ->with('success', "تم نقل النزيل من {$oldLabel} إلى {$newLabel}" .
                ($newTotal != (float) $old['total_amount'] ? ' — الإجمالي الجديد: ' . number_format($newTotal, 0) . ' ر.ي' : '') .
                ' — الغرفة القديمة في وضع الفحص');
    }

    public function cancel(Request $request, Reservation $reservation)
    {
        if ($reservation->status === 'checked_out') {
            return back()->with('error', 'لا يمكن إلغاء حجز مكتمل (تسجيل الخروج تم)');
        }

        $rules = [
            'cancellation_reason' => 'required|string|max:500',
        ];
        $messages = [
            'cancellation_reason.required' => 'يجب إدخال سبب الإلغاء',
        ];

        // إذا كان على الحجز مبلغ مدفوع، يجب تسجيل استرجاعه — لا يجوز إلغاء الحجز
        // والاحتفاظ بمال النزيل دون توثيق كيفية إعادته
        if ((float) $reservation->paid_amount > 0) {
            $rules['refund_amount'] = 'required|numeric|min:0.01|max:' . $reservation->paid_amount;
            $rules['refund_method'] = 'required|in:cash,bank_transfer,pos';
            $messages['refund_amount.required'] = 'يجب تسجيل مبلغ الاسترجاع لأن الحجز عليه مبلغ مدفوع';
            $messages['refund_amount.max']      = 'لا يمكن استرجاع أكثر مما تم دفعه (' . number_format($reservation->paid_amount, 0) . ' ر.ي)';
            $messages['refund_method.required'] = 'طريقة الاسترجاع مطلوبة';
        }

        $validated = $request->validate($rules, $messages);

        DB::transaction(function () use ($reservation, $validated) {
            // Free the room before cancelling
            if ($reservation->room && $reservation->room->status === 'occupied') {
                $reservation->room->update(['status' => 'available']);
            }
            if ($reservation->linkedRoom && $reservation->linkedRoom->status === 'occupied') {
                $reservation->linkedRoom->update(['status' => 'available']);
            }

            if (!empty($validated['refund_amount'])) {
                // لا نُنقص paid_amount هنا: يبقى سجلاً تاريخياً لما دُفع فعلياً أثناء
                // الإقامة، والاسترجاع سجل مالي مستقل يُخصم من الوردية المفتوحة
                // ويظهر في تقرير الاسترجاعات وتقرير المركز المالي.
                app(RefundService::class)->createRefund($reservation, [
                    'amount'   => $validated['refund_amount'],
                    'currency' => 'YER',
                    'method'   => $validated['refund_method'],
                    'reason'   => 'استرجاع بسبب إلغاء الحجز: ' . $validated['cancellation_reason'],
                ], auth()->user(), adjustPaidAmount: false);
            }

            $old = $reservation->toArray();

            // نسجّل سبب الإلغاء ومن ألغى ومتى، ثم حذف ناعم فقط (Reservation تدعم
            // SoftDeletes أصلاً) — تبقى بيانات النزيل والمرافقين والدفعات والمتأخرات
            // محفوظة كاملة في قاعدة البيانات لأغراض المتابعة والتقارير، بدل أن
            // تُحذف نهائياً كما كان يحدث سابقاً مع forceDelete().
            $reservation->update([
                'cancellation_reason' => $validated['cancellation_reason'],
                'cancelled_by'        => auth()->id(),
                'cancelled_at'        => now(),
            ]);

            // 'action' عمود enum ثابت القيم (create/update/delete/...) في جدول audit_logs،
            // فنستخدم 'delete' (الأقرب دلالياً) بدل قيمة جديدة قد ترفضها قاعدة البيانات
            AuditLogService::log('delete', $reservation, $old, $reservation->fresh()->toArray(), auth()->user());

            $reservation->delete();
        });

        $msg = 'تم إلغاء الحجز';
        if (!empty($validated['refund_amount'])) {
            $msg .= ' وتسجيل استرجاع ' . number_format($validated['refund_amount'], 0) . ' ر.ي';
        }
        $msg .= ' — بقيت بياناته محفوظة ويمكن مراجعتها في تقرير أسباب الإلغاء';

        return redirect()->route('reservations.index')->with('success', $msg);
    }

    /**
     * حذف نهائي كامل للحجز وبياناته (للمدير فقط) — لا يمكن التراجع عنه:
     * يمحو الحجز والمرافقين والدفعات والرسوم والاسترجاعات وتقارير الفحص وصورها،
     * يحرّر الغرفة، ويحذف النزيل إن لم يعد مرتبطاً بأي حجز آخر، ثم يعيد احتساب
     * مجاميع الورديات المتأثرة بحذف الدفعات/الاسترجاعات.
     */
    public function destroy(Reservation $reservation)
    {
        DB::transaction(function () use ($reservation) {
            // الورديات المتأثرة لإعادة احتساب مجاميعها بعد حذف الدفعات/الاسترجاعات
            $shiftIds = $reservation->payments()->pluck('shift_id')
                ->merge($reservation->refunds()->pluck('shift_id'))
                ->filter()->unique()->values();

            $guest = $reservation->guest; // withTrashed — قد يكون النزيل محذوفاً ناعماً

            // تحرير الغرف المشغولة بهذا الحجز
            foreach ([$reservation->room, $reservation->linkedRoom] as $room) {
                if ($room && $room->status === 'occupied') {
                    $room->update(['status' => 'available']);
                }
            }

            // حذف السجلات المرتبطة نهائياً
            foreach ($reservation->roomInspections()->get() as $insp) {
                $insp->images()->get()->each->delete();
                $insp->forceDelete();
            }
            $reservation->extraCharges()->get()->each->forceDelete();
            $reservation->payments()->get()->each->forceDelete();
            $reservation->refunds()->get()->each->delete();
            $reservation->companions()->withTrashed()->get()->each->forceDelete();

            $old = $reservation->toArray();
            AuditLogService::log('delete', $reservation, $old, null, auth()->user());

            $reservation->forceDelete();

            // حذف النزيل إن لم يعد مرتبطاً بأي حجز آخر (بما فيها المحذوفة ناعماً)
            if ($guest && !Reservation::withTrashed()->where('guest_id', $guest->id)->exists()) {
                $guest->forceDelete();
            }

            // إعادة احتساب مجاميع الورديات المتأثرة حتى تبقى التقارير متطابقة
            $shiftService = app(ShiftService::class);
            foreach ($shiftIds as $sid) {
                if ($shift = Shift::find($sid)) {
                    $shiftService->computeTotals($shift);
                }
            }
        });

        return redirect()->route('reservations.index')
            ->with('success', 'تم حذف الحجز وجميع بياناته نهائياً');
    }

    public function checkin(Reservation $reservation)
    {
        if ($reservation->status !== 'confirmed') {
            return back()->withErrors(['error' => 'لا يمكن تسجيل دخول حجز بحالة "' . $reservation->status_label . '"']);
        }

        $old = $reservation->only(['status']);

        $reservation->update(['status' => 'checked_in']);

        if ($reservation->room) {
            $reservation->room->update(['status' => 'occupied']);
        }

        AuditLogService::log('update', $reservation, $old, ['status' => 'checked_in'], auth()->user());

        return redirect()->route('reservations.show', $reservation)
            ->with('success', 'تم تسجيل الدخول بنجاح');
    }

    public function expiring(\Illuminate\Http\Request $request)
    {
        // احتساب التجديد التلقائي لكل الإقامات المفعّل بها قبل بناء القائمة
        \App\Models\Reservation::runAutoRenewals();

        // فلتر الحالة: مقيم (الافتراضي) / غادر / الكل
        $status = $request->input('status', 'all');

        $query = \App\Models\Reservation::with(['guest', 'room.roomType']);

        if (in_array($status, ['checked_in', 'checked_out'], true)) {
            $query->where('status', $status);
        } else {
            // "all" — المقيمون والمغادرون فقط (نستثني الملغاة)
            $query->whereIn('status', ['checked_in', 'checked_out']);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('guest', fn($g) => $g->where('full_name', 'like', "%{$search}%"))
                  ->orWhereHas('room', fn($r) => $r->where('room_number', 'like', "%{$search}%"))
                  ->orWhereHas('linkedRoom', fn($r) => $r->where('room_number', 'like', "%{$search}%"));
            });
        }

        if ($checkIn = $request->input('check_in_date')) {
            $query->whereDate('check_in_date', $checkIn);
        }

        if ($checkOut = $request->input('check_out_date')) {
            $query->whereDate('check_out_date', $checkOut);
        }

        // من لم يغادروا أولاً (الأقرب لموعد الخروج أعلى القائمة)، ثم من غادروا في النهاية
        $reservations = $query->orderByRaw("CASE WHEN status = 'checked_in' THEN 0 ELSE 1 END")
            ->orderBy('check_out_date', 'asc')
            ->get();

        return view('reservations.expiring', compact('reservations', 'status'));
    }

    public function invoice(Reservation $reservation)
    {
        $reservation->load(['guest', 'room.roomType', 'payments.receivedBy', 'extraCharges', 'createdBy', 'roomInspections.images']);

        $pdf = pdf_load_view('reservations.invoice', compact('reservation'));
        $pdf->setPaper('a4', 'portrait');

        $dompdf = $pdf->getDomPDF();
        $opts   = $dompdf->getOptions();
        $opts->setFontDir(storage_path('fonts'));
        $opts->setFontCache(storage_path('fonts'));
        $dompdf->setOptions($opts);

        $filename = 'invoice-' . str_pad($reservation->id, 6, '0', STR_PAD_LEFT) . '.pdf';
        return $pdf->stream($filename);
    }

    public function applyDiscount(Request $request, Reservation $reservation)
    {
        $validated = $request->validate([
            'discount_type'   => 'required|in:fixed,percent',
            'discount_value'  => 'required|numeric|min:0',
            'discount_reason' => 'nullable|string|max:255',
        ], [
            'discount_type.required'  => 'نوع الخصم مطلوب',
            'discount_value.required' => 'قيمة الخصم مطلوبة',
        ]);

        // نحسب على الإجمالي قبل الخصم (الصافي الحالي + أي خصم سابق) حتى يحلّ الخصم
        // الجديد محلّ القديم بدل أن يُضاف فوقه.
        $baseTotal = $reservation->gross_total;

        if ($validated['discount_type'] === 'percent') {
            $discountAmount = round($baseTotal * min($validated['discount_value'], 100) / 100, 2);
        } else {
            $discountAmount = round(min((float)$validated['discount_value'], $baseTotal), 2);
        }

        // الصافي بعد الخصم (للغرفة) + الرسوم الإضافية = الإجمالي الجديد (مقرَّب لأقرب ريال)
        $newTotal = round(max(0, round($baseTotal - $discountAmount, 2)) + $reservation->extra_charges_total, 0);

        $reservation->update([
            'discount_type'   => $validated['discount_type'],
            'discount_value'  => $validated['discount_value'],
            'discount_amount' => $discountAmount,
            'discount_reason' => $validated['discount_reason'] ?? null,
            'total_amount'    => $newTotal,
        ]);

        $reservation->refresh()->updatePaymentStatus();

        // 'action' عمود enum ثابت القيم في audit_logs — لا يقبل 'discount_applied'
        AuditLogService::log('update', $reservation, null, [
            'discount_type'   => $validated['discount_type'],
            'discount_value'  => $validated['discount_value'],
            'discount_amount' => $discountAmount,
        ]);

        return redirect()->route('reservations.show', $reservation)
            ->with('success', 'تم تطبيق الخصم بنجاح — ' . number_format($discountAmount, 0) . ' ر.ي');
    }

    /**
     * تسجيل أضرار لنزيل مقيم (دون انتظار تسجيل الخروج) — يُنشئ سجل فحص/أضرار مع
     * الصور، ويضيف قيمة التعويض كرسم إضافي إلى إجمالي الحجز (دَين على النزيل)
     * ويسجّلها مصروف صيانة، تماماً كما يحدث عند الخروج.
     */
    public function addDamage(Request $request, Reservation $reservation)
    {
        if ($reservation->status !== 'checked_in') {
            return back()->withErrors(['error' => 'تسجيل الأضرار متاح فقط للنزلاء المقيمين (مسجّل دخول)']);
        }

        $validated = $request->validate([
            'damage_description'  => 'required|string|max:1000',
            'compensation_amount' => 'required|numeric|min:0',
            'damage_images.*'     => 'nullable|file|mimes:jpg,jpeg,png|max:5120',
        ], [
            'damage_description.required'  => 'وصف الأضرار مطلوب',
            'compensation_amount.required' => 'مبلغ التعويض مطلوب',
            'compensation_amount.numeric'  => 'مبلغ التعويض يجب أن يكون رقماً',
        ]);

        $amount = (float) $validated['compensation_amount'];

        DB::transaction(function () use ($reservation, $validated, $amount, $request) {
            $inspection = \App\Models\RoomInspection::create([
                'reservation_id'      => $reservation->id,
                'inspected_by'        => auth()->id(),
                'has_damage'          => true,
                'damage_description'  => $validated['damage_description'],
                'compensation_amount' => $amount,
                'compensation_status' => $amount > 0 ? 'pending' : 'none',
                'inspection_date'     => now(),
            ]);

            if ($request->hasFile('damage_images')) {
                foreach ($request->file('damage_images') as $image) {
                    \App\Models\InspectionImage::create([
                        'room_inspection_id' => $inspection->id,
                        'image_path'         => $image->store('inspection_images', 'private'),
                    ]);
                }
            }

            if ($amount > 0) {
                \App\Models\ExtraCharge::create([
                    'reservation_id' => $reservation->id,
                    'added_by'       => auth()->id(),
                    'type'           => 'damage',
                    'description'    => $validated['damage_description'],
                    'amount'         => $amount,
                    'charge_date'    => now(),
                ]);

                \App\Models\Expense::create([
                    'amount'       => $amount,
                    'currency'     => 'YER',
                    'category'     => 'maintenance',
                    'description'  => 'أضرار غرفة ' . ($reservation->room->room_number ?? '') . ' — ' . $validated['damage_description'],
                    'expense_date' => now()->toDateString(),
                    'paid_by'      => auth()->id(),
                    'shift_id'     => null,
                ]);

                $reservation->increment('total_amount', $amount);
                $reservation->refresh()->updatePaymentStatus();
            }

            AuditLogService::log('update', $reservation, null, [
                'action'              => 'damage_recorded',
                'compensation_amount' => $amount,
                'damage_description'  => $validated['damage_description'],
            ], auth()->user());
        });

        return redirect()->route('reservations.show', $reservation)
            ->with('success', 'تم تسجيل الأضرار' . ($amount > 0 ? ' وإضافة ' . number_format($amount, 0) . ' ر.ي إلى حساب النزيل' : ''));
    }

    /**
     * إضافة رسم/مصروف على حساب النزيل (مشتريات بقالة، مأكولات، خدمات...) — يُضاف
     * إلى إجمالي الحجز فيصبح ديناً يُحصَّل مع الليالي عند المغادرة.
     */
    public function addCharge(Request $request, Reservation $reservation)
    {
        if (!in_array($reservation->status, ['checked_in', 'checked_out'])) {
            return back()->withErrors(['error' => 'يمكن إضافة الرسوم لنزيل مقيم أو لم تُسوَّ مغادرته بعد']);
        }

        $validated = $request->validate([
            'charge_type' => 'required|string|max:50',
            'description' => 'nullable|string|max:255',
            'amount'      => 'required|numeric|min:0.01',
        ], [
            'charge_type.required' => 'نوع الرسم مطلوب',
            'amount.required'      => 'المبلغ مطلوب',
            'amount.min'           => 'المبلغ يجب أن يكون أكبر من صفر',
        ]);

        $amount = (float) $validated['amount'];

        DB::transaction(function () use ($reservation, $validated, $amount) {
            \App\Models\ExtraCharge::create([
                'reservation_id' => $reservation->id,
                'added_by'       => auth()->id(),
                'type'           => $validated['charge_type'],
                'description'    => $validated['description'] ?? null,
                'amount'         => $amount,
                'charge_date'    => now(),
            ]);

            $reservation->increment('total_amount', $amount);
            $reservation->refresh()->updatePaymentStatus();

            AuditLogService::log('update', $reservation, null, [
                'action'      => 'charge_added',
                'charge_type' => $validated['charge_type'],
                'amount'      => $amount,
            ], auth()->user());
        });

        return redirect()->route('reservations.show', $reservation)
            ->with('success', 'تمت إضافة ' . number_format($amount, 0) . ' ر.ي إلى حساب النزيل');
    }

    private function nullIfEmpty(mixed $value): mixed
    {
        return ($value === '' || $value === null) ? null : $value;
    }

    /**
     * عند تخفيض إجمالي الحجز إلى ما دون المبلغ المدفوع (تصحيح خطأ في السعر)،
     * يُخفّض هذا التابع الدفعات المرتبطة بالحجز لتطابق الإجمالي الجديد بدءاً
     * من الأحدث، ثم يُعيد حساب المبلغ المدفوع وحالة الدفع وإجماليات الورديات
     * المتأثرة — حتى تعكس الوردية السعر المصحَّح لا القديم.
     */
    private function reconcileOverpayment(Reservation $reservation): void
    {
        $reservation->refresh();
        $excess = round((float) $reservation->paid_amount - (float) $reservation->total_amount, 2);
        if ($excess <= 0) {
            return; // لا يوجد فائض — لا حاجة لأي تعديل على الدفعات أو الوردية
        }

        // نُخفّض الدفعات النقدية للحجز بدءاً من الأحدث حتى يُستوعب الفائض بالكامل
        $payments = $reservation->payments()
            ->where('currency', 'YER')
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->get();

        $affectedShiftIds = [];

        foreach ($payments as $payment) {
            if ($excess <= 0) {
                break;
            }

            $affectedShiftIds[$payment->shift_id] = $payment->shift_id;
            $amount = (float) $payment->amount;

            if ($amount <= $excess) {
                // الدفعة بالكامل ضمن الفائض → نحذفها
                $excess = round($excess - $amount, 2);
                $payment->delete();
            } else {
                // تخفيض جزئي يطابق ما تبقى من الفائض
                $payment->update(['amount' => round($amount - $excess, 2)]);
                $excess = 0;
            }
        }

        // إعادة حساب المبلغ المدفوع من الدفعات المتبقية ثم تحديث حالة الدفع
        $newPaid = (float) $reservation->payments()->where('currency', 'YER')->sum('amount');
        $reservation->update(['paid_amount' => $newPaid]);
        $reservation->updatePaymentStatus();

        // إعادة حساب إجماليات كل وردية تأثّرت بتعديل دفعاتها
        $shiftService = app(ShiftService::class);
        foreach (array_filter($affectedShiftIds) as $shiftId) {
            if ($shift = Shift::find($shiftId)) {
                $shiftService->computeTotals($shift);
            }
        }
    }
}
