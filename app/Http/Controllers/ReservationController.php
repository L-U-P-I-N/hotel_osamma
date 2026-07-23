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

        // تعبئة فترات الغرفة للحجوزات القائمة التي أُنشئت قبل ميزة التفصيل (مرّة واحدة)
        if (!$reservation->segments()->exists() && in_array($reservation->status, ['checked_in', 'checked_out'])) {
            app(\App\Services\ReservationSegmentService::class)->backfillFromHistory($reservation);
        }

        $reservation->load(['guest', 'room.roomType', 'companions', 'payments', 'extraCharges', 'roomInspections.images', 'createdBy', 'adminApproval', 'segments']);
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

        // حجوزات نشطة أخرى يمكن تبديل الغرفة معها (تصحيح خطأ إدخال نزيلين في غرفتَي
        // بعضهما) — نزيل مقيم في غرفة مختلفة عن هذا الحجز.
        $swappableReservations = $reservation->status === 'checked_in'
            ? Reservation::with(['guest', 'room'])
                ->where('status', 'checked_in')
                ->where('id', '!=', $reservation->id)
                ->orderBy('room_id')
                ->get()
            : collect();

        return view('reservations.show', compact('reservation', 'availableRooms', 'transferOptions', 'renewMaxCheckout', 'renewNextArrival', 'swappableReservations'));
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

        // عدد الأيام حسب وقت الخروج (حد 1 ظهراً) — لتوحيد اشتقاق سعر الليلة
        $nights = ($reservation->check_in_date && $reservation->check_out_date)
            ? $reservation->nights
            : 0;
        // سعر الليلة الأولى في نموذج التعديل: إن كان محفوظاً صراحةً نستخدمه كما هو
        // (حتى لا ينحرف الإجمالي عند إعادة الحفظ)، وإلا نشتقّه من إجمالي الغرفة قبل
        // الخصم (بدون الرسوم الإضافية) مقسوماً على الليالي — دون احتساب مشتريات/أضرار النزيل.
        $currentPricePerNight = $reservation->first_night_price !== null
            ? (float) $reservation->first_night_price
            : ($nights > 0
                ? round($reservation->gross_total / $nights, 2)
                : ($reservation->room?->roomType?->base_price ?? 0));

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

                // إن كان سجل النزيل مشتركاً مع حجوزات أخرى (نفس guest_id لأكثر من
                // حجز)، فتعديله مباشرةً يُغيّر اسم/بيانات نزلاء تلك الحجوزات الأخرى
                // أيضاً — وهو ما حدث فعلاً بين غرفتين لنزيلين مختلفين اختلط سجلهما.
                // نتجنّب ذلك بنسخ السجل إلى نزيل جديد خاص بهذا الحجز (copy-on-write)
                // ونربط الحجز به، فلا تتأثّر بقية الحجوزات. أما إن كان السجل خاصاً
                // بهذا الحجز وحده فنعدّله مباشرةً كالمعتاد.
                $sharedByOthers = \App\Models\Reservation::withTrashed()
                    ->where('guest_id', $reservation->guest_id)
                    ->where('id', '!=', $reservation->id)
                    ->exists();

                if ($sharedByOthers) {
                    $newGuest = $reservation->guest->replicate();
                    $newGuest->fill($guestData);
                    $newGuest->save();
                    $reservation->guest_id = $newGuest->id;
                    $reservation->save();
                    $reservation->setRelation('guest', $newGuest);
                } else {
                    $reservation->guest->update($guestData);
                }
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

            // عدد الأيام حسب حد 1 ظهراً اعتماداً على لحظتي الوصول والخروج. الأوقات لا
            // تُعدَّل من هذا النموذج فنستخدم المحفوظة في الحجز مع التواريخ الجديدة.
            $billableNights = Reservation::billableNightsFor(
                $validated['check_in_date'],
                $validated['check_out_date'],
                $reservation->check_out_time,
                $reservation->check_in_time
            );

            // نموذج التسعير: الليلة الأولى بسعرها الخاص + بقية الليالي (بما فيها كل
            // التجديدات) بسعر التجديد. لذا تعديل سعر التجديد هنا يعيد حساب كامل مدة
            // الإقامة فيصحّح التجديدات السابقة أيضاً — لا القادمة فقط.
            $submittedFirstNight = isset($validated['price_per_night']) && $validated['price_per_night'] > 0
                ? (float) $validated['price_per_night']
                : null;
            $submittedRenewal = isset($validated['renewal_price_per_night']) && $validated['renewal_price_per_night'] !== ''
                ? (float) $validated['renewal_price_per_night']
                : null;

            // عند غياب سعر مُرسَل أو محفوظ صراحةً، نشتقّه من السعر الفعّال الحالي
            // (الإجمالي ÷ الليالي) لا سعر الغرفة الافتراضي العام — فقد يكون هذا
            // الحجز مسعَّراً بسعر متفاوَض عليه يختلف عن سعر الغرفة الافتراضي.
            $firstNightPrice = $submittedFirstNight
                ?? ($reservation->first_night_price !== null ? (float) $reservation->first_night_price : null)
                ?? ($billableNights > 0 ? round($reservation->gross_total / $billableNights, 2) : null)
                ?? $reservation->room?->roomType?->base_price
                ?? 0;
            // إن لم يُحدَّد سعر تجديد صراحةً نستخدم سعر الليلة الأولى نفسه لبقية الليالي
            $renewalPrice = $submittedRenewal
                ?? ($reservation->renewal_price_per_night !== null ? (float) $reservation->renewal_price_per_night : null)
                ?? $firstNightPrice;

            // إجمالي الغرفة قبل الخصم = الليلة الأولى + (بقية الليالي × سعر التجديد)،
            // ثم نطبّق الخصم ونُعيد الرسوم الإضافية حتى لا يضيع الخصم ولا تُفقد مصروفات النزيل
            $grossTotal = round($firstNightPrice + max(0, $billableNights - 1) * $renewalPrice, 2);
            $discountAmount = $reservation->discountAmountFor($grossTotal);
            $newTotal = round(max(0, round($grossTotal - $discountAmount, 2)) + $reservation->hotel_charges_total, 0);

            $reservation->update([
                'check_in_date'  => $validated['check_in_date'],
                'check_out_date' => $validated['check_out_date'],
                'purpose'        => $this->nullIfEmpty($validated['purpose'] ?? null),
                'origin'         => $this->nullIfEmpty($validated['origin'] ?? null),
                'notes'          => $this->nullIfEmpty($validated['notes'] ?? null),
                'discount_amount'=> $discountAmount,
                'total_amount'   => $newTotal,
                // نحفظ سعر الليلة الأولى فقط عند وجود أكثر من ليلة واختلافه عن سعر التجديد
                // حتى يظهر تفصيل السعرين في صفحة الحجز والفاتورة، وإلا فسعر موحّد
                'first_night_price' => ($billableNights > 1 && round($firstNightPrice, 2) != round($renewalPrice, 2))
                    ? $firstNightPrice : null,
                'renewal_price_per_night' => $submittedRenewal,
            ]);

            // مهم: تعديل السعر لا يمسّ الدفعات المسجَّلة إطلاقاً — المبلغ المدفوع حقيقةٌ
            // تاريخية لا يجوز حذفها عند تصحيح التسعيرة. إن قلّ الإجمالي الجديد عن المدفوع
            // يظهر ذلك كفائض/رصيد للنزيل يُعالَج عبر الاسترجاع، لا بإنقاص دفعاته.
            $reservation->refresh();
            $reservation->updatePaymentStatus();

            // إعادة بناء فترات الغرفة وفق التسعيرة الجديدة (ليلة أولى + باقي الليالي)
            app(\App\Services\ReservationSegmentService::class)
                ->rebuildFromCurrentPricing($reservation, $firstNightPrice, $renewalPrice, $billableNights, auth()->id());

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
        $newTotal       = round(max(0, round($newGross - $discountAmount, 2)) + $reservation->hotel_charges_total, 0);

        $old = $reservation->only(['check_out_date', 'total_amount', 'renewal_price_per_night']);

        // نُلحق ملاحظة المستخدم دائماً بعلامة التجديد (كانت تُهمل سابقاً إن وُجدت ملاحظات قديمة)
        $renewalNote = "[تجديد +{$extraNights} ليلة بسعر " . number_format($pricePerNight, 0) . " ر.ي/ليلة]"
            . (!empty($validated['notes']) ? ': ' . $validated['notes'] : '');

        $oldCheckOut = $reservation->check_out_date->copy();

        // الوردية المفتوحة حالياً (إن وُجدت) — تُربط بها فترة التجديد حتى يُمنَع
        // لاحقاً تعديل/حذف تجديدٍ يخصّ وردية أُقفلت بالفعل
        $shiftService = app(\App\Services\ShiftService::class);
        $shift = $shiftService->getActiveShift(auth()->user());

        $reservation->update([
            'check_out_date'          => $validated['new_check_out_date'],
            'total_amount'            => $newTotal,
            'discount_amount'         => $discountAmount,
            'renewal_price_per_night' => $pricePerNight,
            'notes'                   => $reservation->notes
                                    ? $reservation->notes . "\n" . $renewalNote
                                    : $renewalNote,
        ]);

        // تسجيل فترة التجديد كسطر مستقل (لعرض تفصيل أسعار الغرفة)
        app(\App\Services\ReservationSegmentService::class)->recordRenewal(
            $reservation,
            $oldCheckOut,
            $validated['new_check_out_date'],
            $extraNights,
            $pricePerNight,
            $extraAmount,
            auth()->id(),
            $shift?->id
        );

        if (!empty($validated['advance_payment']) && $validated['advance_payment'] > 0) {
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
     * بخلاف نموذج تعديل الحجز العام الذي يعيد حساب الإجمالي من عدد الليالي.
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
        $newTotal       = round(max(0, round($grossTotal - $discountAmount, 2)) + $reservation->hotel_charges_total, 0);

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

    /**
     * تبديل الغرفتين بين نزيلين نشطين (تصحيح إدخال نزيلين في غرفتَي بعضهما بالخطأ).
     * يتبادل الحجزان تعيين الغرفة فقط (room_id + linked_room_id + suite_booking_type)
     * — تبقى أسعار كلٍّ ودفعاته وتواريخه معه (فبيانات المال تخصّ النزيل لا الغرفة)،
     * وتبقى الغرفتان مشغولتين (تبادل مكان لا إخلاء). لا يغيّر أي إجمالي.
     */
    public function swapRoom(Request $request, Reservation $reservation)
    {
        $validated = $request->validate([
            'target_reservation_id' => 'required|integer|exists:reservations,id',
        ], [
            'target_reservation_id.required' => 'يرجى اختيار النزيل الآخر للتبديل معه',
        ]);

        if ($reservation->status !== 'checked_in') {
            return back()->withErrors(['error' => 'التبديل متاح فقط للحجوزات النشطة (مسجل دخول)']);
        }

        $other = Reservation::with(['guest', 'room'])->find($validated['target_reservation_id']);
        if (!$other || $other->id === $reservation->id) {
            return back()->withErrors(['error' => 'اختيار النزيل الآخر غير صالح']);
        }
        if ($other->status !== 'checked_in') {
            return back()->withErrors(['error' => 'النزيل الآخر يجب أن يكون مقيماً (مسجل دخول)']);
        }
        if ($other->room_id === $reservation->room_id) {
            return back()->withErrors(['error' => 'النزيلان في نفس الغرفة — لا حاجة للتبديل']);
        }

        $aLabel = $reservation->guest?->full_name . ' (غرفة ' . $reservation->display_room_number . ')';
        $bLabel = $other->guest?->full_name . ' (غرفة ' . $other->display_room_number . ')';

        DB::transaction(function () use ($reservation, $other, $aLabel, $bLabel) {
            $oldA = $reservation->only(['room_id', 'linked_room_id', 'suite_booking_type']);
            $oldB = $other->only(['room_id', 'linked_room_id', 'suite_booking_type']);

            $swapNote = "[تبديل غرفة: {$aLabel} ↔ {$bLabel}]";

            // تبادل تعيين الغرفة بين الحجزين (الأسعار/الدفعات/التواريخ تبقى مع كلٍّ)
            $reservation->update([
                'room_id'            => $oldB['room_id'],
                'linked_room_id'     => $oldB['linked_room_id'],
                'suite_booking_type' => $oldB['suite_booking_type'],
                'notes'              => $reservation->notes ? $reservation->notes . "\n" . $swapNote : $swapNote,
            ]);
            $other->update([
                'room_id'            => $oldA['room_id'],
                'linked_room_id'     => $oldA['linked_room_id'],
                'suite_booking_type' => $oldA['suite_booking_type'],
                'notes'              => $other->notes ? $other->notes . "\n" . $swapNote : $swapNote,
            ]);

            AuditLogService::log('update', $reservation, $oldA, array_merge($reservation->only(['room_id', 'linked_room_id', 'suite_booking_type']), ['action' => 'room_swap']), auth()->user());
            AuditLogService::log('update', $other, $oldB, array_merge($other->only(['room_id', 'linked_room_id', 'suite_booking_type']), ['action' => 'room_swap']), auth()->user());
        });

        return redirect()->route('reservations.show', $reservation)
            ->with('success', "تم تبديل الغرفتين بنجاح: {$aLabel} ↔ {$bLabel}");
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
        if (!$reservation->segments()->exists() && in_array($reservation->status, ['checked_in', 'checked_out'])) {
            app(\App\Services\ReservationSegmentService::class)->backfillFromHistory($reservation);
        }

        $reservation->load(['guest', 'room.roomType', 'payments.receivedBy', 'extraCharges', 'createdBy', 'roomInspections.images', 'segments']);

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
        $newTotal = round(max(0, round($baseTotal - $discountAmount, 2)) + $reservation->hotel_charges_total, 0);

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
     * إلغاء الخصم المطبَّق على الحجز (خصم أُدخل بالخطأ) — يُعيد الإجمالي إلى قيمته
     * قبل الخصم (إجمالي الغرفة + الرسوم الإضافية) ويمحو حقول الخصم، ثم يُعيد حساب
     * حالة الدفع. لا يمسّ أي دفعة مسجَّلة.
     */
    public function removeDiscount(Reservation $reservation)
    {
        if ((float) $reservation->discount_amount <= 0 && !$reservation->discount_type) {
            return back()->withErrors(['error' => 'لا يوجد خصم مطبَّق على هذا الحجز']);
        }

        $old = $reservation->only(['discount_type', 'discount_value', 'discount_amount', 'discount_reason', 'total_amount']);

        // الإجمالي قبل الخصم = إجمالي الغرفة (gross) + الرسوم الإضافية، مقرَّباً لأقرب ريال.
        $newTotal = round((float) $reservation->gross_total + $reservation->hotel_charges_total, 0);

        $reservation->update([
            'discount_type'   => null,
            'discount_value'  => 0,
            'discount_amount' => 0,
            'discount_reason' => null,
            'total_amount'    => $newTotal,
        ]);

        $reservation->refresh()->updatePaymentStatus();

        AuditLogService::log('update', $reservation, $old, [
            'action'       => 'discount_removed',
            'total_amount' => $newTotal,
        ], auth()->user());

        return redirect()->route('reservations.show', $reservation)
            ->with('success', 'تم إلغاء الخصم وإرجاع الإجمالي إلى ما قبل الخصم');
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
                    'reservation_id'     => $reservation->id,
                    'room_inspection_id' => $inspection->id,
                    'added_by'           => auth()->id(),
                    'type'               => 'damage',
                    'description'        => $validated['damage_description'],
                    'amount'             => $amount,
                    'charge_date'        => now(),
                ]);

                \App\Models\Expense::create([
                    'amount'             => $amount,
                    'currency'           => 'YER',
                    'category'           => 'maintenance',
                    'description'        => 'أضرار غرفة ' . ($reservation->room->room_number ?? '') . ' — ' . $validated['damage_description'],
                    'expense_date'       => now()->toDateString(),
                    'paid_by'            => auth()->id(),
                    'shift_id'           => null,
                    'room_inspection_id' => $inspection->id,
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
     * تعديل ضرر مسجّل: تحديث الوصف ومبلغ التعويض، مع مزامنة الرسم الإضافي (الدَّين)
     * ومصروف الصيانة وإجمالي الحجز بفارق المبلغ فقط، ثم إعادة احتساب حالة الدفع.
     */
    public function updateDamage(Request $request, Reservation $reservation, \App\Models\RoomInspection $inspection)
    {
        if ($inspection->reservation_id !== $reservation->id) {
            abort(404);
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

        $newAmount = (float) $validated['compensation_amount'];
        $oldAmount = (float) $inspection->compensation_amount;
        $delta     = round($newAmount - $oldAmount, 2);

        DB::transaction(function () use ($reservation, $inspection, $validated, $newAmount, $oldAmount, $delta, $request) {
            $inspection->update([
                'has_damage'          => true,
                'damage_description'  => $validated['damage_description'],
                'compensation_amount' => $newAmount,
                'compensation_status' => $newAmount > 0 ? ($inspection->compensation_status === 'paid' ? 'paid' : 'pending') : 'none',
            ]);

            // صور إضافية جديدة (اختياري) — تُضاف دون حذف القديمة
            if ($request->hasFile('damage_images')) {
                foreach ($request->file('damage_images') as $image) {
                    \App\Models\InspectionImage::create([
                        'room_inspection_id' => $inspection->id,
                        'image_path'         => $image->store('inspection_images', 'private'),
                    ]);
                }
            }

            // مزامنة الرسم الإضافي (الدَّين على النزيل)
            $charge = $inspection->extraCharge()->first();
            if ($newAmount > 0) {
                if ($charge) {
                    $charge->update([
                        'description' => $validated['damage_description'],
                        'amount'      => $newAmount,
                    ]);
                } else {
                    \App\Models\ExtraCharge::create([
                        'reservation_id'     => $reservation->id,
                        'room_inspection_id' => $inspection->id,
                        'added_by'           => auth()->id(),
                        'type'               => 'damage',
                        'description'        => $validated['damage_description'],
                        'amount'             => $newAmount,
                        'charge_date'        => now(),
                    ]);
                }
            } elseif ($charge) {
                $charge->delete();
            }

            // مزامنة مصروف الصيانة
            $expense = $inspection->expense()->first();
            $expenseDescription = 'أضرار غرفة ' . ($reservation->room->room_number ?? '') . ' — ' . $validated['damage_description'];
            if ($newAmount > 0) {
                if ($expense) {
                    $expense->update([
                        'amount'      => $newAmount,
                        'description' => $expenseDescription,
                    ]);
                } else {
                    \App\Models\Expense::create([
                        'amount'             => $newAmount,
                        'currency'           => 'YER',
                        'category'           => 'maintenance',
                        'description'        => $expenseDescription,
                        'expense_date'       => now()->toDateString(),
                        'paid_by'            => auth()->id(),
                        'shift_id'           => null,
                        'room_inspection_id' => $inspection->id,
                    ]);
                }
            } elseif ($expense) {
                $expense->delete();
            }

            // تعديل إجمالي الحجز بفارق المبلغ فقط
            if ($delta !== 0.0) {
                $reservation->total_amount = max(0, round((float) $reservation->total_amount + $delta, 2));
                $reservation->save();
            }
            $reservation->refresh()->updatePaymentStatus();

            AuditLogService::log('update', $reservation, ['compensation_amount' => $oldAmount], [
                'action'              => 'damage_updated',
                'compensation_amount' => $newAmount,
                'damage_description'  => $validated['damage_description'],
            ], auth()->user());
        });

        return redirect()->route('reservations.show', $reservation)
            ->with('success', 'تم تعديل الضرر المسجّل وتحديث حساب النزيل');
    }

    /**
     * حذف ضرر مسجّل: عكس أثره المالي بالكامل — إنقاص الإجمالي بمبلغ التعويض، وحذف
     * الرسم الإضافي ومصروف الصيانة والصور المرتبطة، ثم إعادة احتساب حالة الدفع.
     */
    public function removeDamage(Reservation $reservation, \App\Models\RoomInspection $inspection)
    {
        if ($inspection->reservation_id !== $reservation->id) {
            abort(404);
        }

        $amount = (float) $inspection->compensation_amount;

        DB::transaction(function () use ($reservation, $inspection, $amount) {
            // حذف الرسم الإضافي (الدَّين) ومصروف الصيانة المرتبطَين
            $inspection->extraCharge()->get()->each->delete();
            $inspection->expense()->get()->each->delete();

            // حذف صور الأضرار (الملفات ثم السجلات)
            foreach ($inspection->images()->get() as $img) {
                \Illuminate\Support\Facades\Storage::disk('private')->delete($img->image_path);
                $img->delete();
            }

            $inspection->forceDelete();

            // إنقاص إجمالي الحجز بمبلغ التعويض المحذوف
            if ($amount > 0) {
                $reservation->total_amount = max(0, round((float) $reservation->total_amount - $amount, 2));
                $reservation->save();
                $reservation->refresh()->updatePaymentStatus();
            }

            AuditLogService::log('update', $reservation, ['compensation_amount' => $amount], [
                'action'              => 'damage_deleted',
                'compensation_amount' => $amount,
            ], auth()->user());
        });

        return redirect()->route('reservations.show', $reservation)
            ->with('success', 'تم حذف الضرر المسجّل' . ($amount > 0 ? ' وخصم ' . number_format($amount, 0) . ' ر.ي من حساب النزيل' : ''));
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
            // رسم مشتريات (بقالة/خدمات) — دَين على النزيل منفصل عن صندوق الفندق،
            // لا يُضاف إلى إجمالي الحجز ولا يظهر في تقارير الفندق. يُحصَّل عند
            // الخروج ويُسلَّم للبقالة.
            \App\Models\ExtraCharge::create([
                'reservation_id' => $reservation->id,
                'added_by'       => auth()->id(),
                'type'           => $validated['charge_type'],
                'description'    => $validated['description'] ?? null,
                'amount'         => $amount,
                'charge_date'    => now(),
                'in_hotel_total' => false,
                'settled_at'     => null,
            ]);

            AuditLogService::log('update', $reservation, null, [
                'action'      => 'purchase_charge_added',
                'charge_type' => $validated['charge_type'],
                'amount'      => $amount,
            ], auth()->user());
        });

        return redirect()->route('reservations.show', $reservation)
            ->with('success', 'تمت إضافة ' . number_format($amount, 0) . ' ر.ي كدَين مشتريات (بقالة) على النزيل — يُحصَّل عند الخروج');
    }

    /**
     * تعديل رسم إضافي: تحديث النوع/الوصف/المبلغ، مع تعديل إجمالي الحجز بفارق المبلغ
     * فقط، ثم إعادة احتساب حالة الدفع.
     */
    public function updateCharge(Request $request, \App\Models\ExtraCharge $charge)
    {
        $reservation = $charge->reservation;
        if (!$reservation || !in_array($reservation->status, ['checked_in', 'checked_out'])) {
            abort(404);
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

        $newAmount = (float) $validated['amount'];
        $oldAmount = (float) $charge->amount;
        $delta     = round($newAmount - $oldAmount, 2);
        // رسوم المشتريات (in_hotel_total = false) خارج إجمالي الحجز؛ لا نمسّ الإجمالي
        // عند تعديلها. الرسوم القديمة المحتسَبة في الإجمالي تُعدَّل بالفارق كالسابق.
        $affectsTotal = (bool) $charge->in_hotel_total;

        DB::transaction(function () use ($reservation, $charge, $validated, $newAmount, $oldAmount, $delta, $affectsTotal) {
            $charge->update([
                'type'        => $validated['charge_type'],
                'description' => $validated['description'] ?? null,
                'amount'      => $newAmount,
            ]);

            if ($delta !== 0.0 && $affectsTotal) {
                $reservation->total_amount = max(0, round((float) $reservation->total_amount + $delta, 2));
                $reservation->save();
            }
            $reservation->refresh()->updatePaymentStatus();

            AuditLogService::log('update', $reservation, ['amount' => $oldAmount], [
                'action'       => 'charge_updated',
                'charge_type'  => $validated['charge_type'],
                'amount'       => $newAmount,
            ], auth()->user());
        });

        return redirect()->route('reservations.show', $reservation)
            ->with('success', 'تم تعديل الرسم بنجاح');
    }

    /**
     * حذف رسم إضافي: عكس أثره المالي بالكامل — إنقاص الإجمالي بمبلغ الرسم،
     * ثم إعادة احتساب حالة الدفع.
     */
    public function deleteCharge(\App\Models\ExtraCharge $charge)
    {
        $reservation = $charge->reservation;
        if (!$reservation || !in_array($reservation->status, ['checked_in', 'checked_out'])) {
            abort(404);
        }

        $amount = (float) $charge->amount;
        // رسوم المشتريات خارج الإجمالي؛ حذفها لا يمسّه. الرسوم القديمة المحتسَبة
        // في الإجمالي تُخصَم منه عند الحذف كالسابق.
        $affectsTotal = (bool) $charge->in_hotel_total;

        DB::transaction(function () use ($reservation, $charge, $amount, $affectsTotal) {
            $charge->delete();

            if ($amount > 0 && $affectsTotal) {
                $reservation->total_amount = max(0, round((float) $reservation->total_amount - $amount, 2));
                $reservation->save();
                $reservation->refresh()->updatePaymentStatus();
            }

            AuditLogService::log('update', $reservation, ['amount' => $amount], [
                'action' => 'charge_deleted',
                'amount' => $amount,
            ], auth()->user());
        });

        return redirect()->route('reservations.show', $reservation)
            ->with('success', 'تم حذف الرسم' . ($amount > 0 && $affectsTotal ? ' وخصم ' . number_format($amount, 0) . ' ر.ي من حساب النزيل' : ''));
    }

    /**
     * تحصيل دَين المشتريات (بقالة/خدمات) وتسليمه للبقالة — يوثّق التحصيل فقط
     * (settled_at) دون أي قيد محاسبي: لا مستلمة ولا وردية ولا تقرير فندق.
     */
    public function settleCharges(Reservation $reservation)
    {
        $outstanding = $reservation->extraCharges()
            ->where('in_hotel_total', false)
            ->whereNull('settled_at')
            ->get();

        if ($outstanding->isEmpty()) {
            return back()->withErrors(['error' => 'لا يوجد دَين مشتريات غير محصّل على هذا الحجز']);
        }

        $total = round((float) $outstanding->sum('amount'), 2);

        DB::transaction(function () use ($outstanding, $reservation, $total) {
            \App\Models\ExtraCharge::whereIn('id', $outstanding->pluck('id'))
                ->update(['settled_at' => now(), 'settled_by' => auth()->id()]);

            AuditLogService::log('update', $reservation, null, [
                'action' => 'purchases_settled',
                'amount' => $total,
            ], auth()->user());
        });

        return redirect()->route('reservations.show', $reservation)
            ->with('success', 'تم تحصيل دَين المشتريات (' . number_format($total, 0) . ' ر.ي) وتسليمه للبقالة');
    }

    /**
     * تعديل سعر فترة واحدة — حجز أولي أو تجديد (مثال: سعر الليلة الأولى أو التجديد
     * رقم 5 دُخِل بسعر خاطئ) — يُحدَّث سعر الليلة ويُعاد احتساب مبلغ الفترة (عدد
     * لياليها × السعر الجديد)، ويُعدَّل إجمالي الحجز بفارق المبلغ فقط. لا يمسّ أي
     * دفعة مسجَّلة ولا أي إيراد وردية، فالدفعات سجلٌّ تاريخي مستقل عن إجمالي الحجز
     * المطلوب. عدد ليالي الفترة وتواريخها لا يتغيران من هنا (فقط السعر) حتى يبقى
     * تسلسل الفترات متّصلاً؛ لتغيير عدد الليالي استخدم إضافة/حذف تجديد.
     */
    public function updateSegment(Request $request, \App\Models\ReservationSegment $segment)
    {
        $reservation = $segment->reservation;
        if (!$reservation || !in_array($reservation->status, ['checked_in', 'checked_out'])) {
            abort(404);
        }
        if ($segment->isLocked()) {
            return back()->withErrors(['error' => 'لا يمكن تعديل هذه الفترة لأنها تخصّ وردية أُقفلت بالفعل — تصحيح سعرها قد يُغيّر أرقام عملٍ سابق مُصفّى.']);
        }

        $validated = $request->validate([
            'price_per_night' => 'required|numeric|min:0',
        ], [
            'price_per_night.required' => 'سعر الليلة مطلوب',
            'price_per_night.numeric'  => 'سعر الليلة يجب أن يكون رقماً',
        ]);

        $newPrice  = (float) $validated['price_per_night'];
        $oldAmount = (float) $segment->amount;
        $newAmount = round($newPrice * $segment->nights, 2);
        $delta     = round($newAmount - $oldAmount, 2);

        DB::transaction(function () use ($reservation, $segment, $newPrice, $newAmount, $oldAmount, $delta) {
            $segment->update([
                'price_per_night' => $newPrice,
                'amount'          => $newAmount,
            ]);

            if ($delta !== 0.0) {
                $reservation->total_amount = max(0, round((float) $reservation->total_amount + $delta, 2));
                $reservation->save();
            }
            $reservation->refresh()->updatePaymentStatus();

            AuditLogService::log('update', $reservation, ['segment_amount' => $oldAmount], [
                'action'          => 'segment_updated',
                'segment_id'      => $segment->id,
                'segment_type'    => $segment->type,
                'price_per_night' => $newPrice,
                'amount'          => $newAmount,
            ], auth()->user());
        });

        return redirect()->route('reservations.show', $reservation)
            ->with('success', 'تم تعديل السعر بنجاح');
    }

    /**
     * حذف فترة تجديد أُضيفت بالخطأ: تُنقَص قيمتها من إجمالي الحجز، ويُزاح تاريخ
     * الخروج وكل الفترات اللاحقة إلى الوراء بعدد لياليها (حتى يبقى تسلسل التواريخ
     * متّصلاً)، ثم تُحذف الفترة. لا يمسّ أي دفعة مسجَّلة. مقصور على فترات التجديد.
     */
    public function deleteSegment(\App\Models\ReservationSegment $segment)
    {
        $reservation = $segment->reservation;
        if (!$reservation || !in_array($reservation->status, ['checked_in', 'checked_out']) || $segment->type !== 'renewal') {
            abort(404);
        }
        if ($segment->isLocked()) {
            return back()->withErrors(['error' => 'لا يمكن حذف هذا التجديد لأنه يخصّ وردية أُقفلت بالفعل — حذفه قد يُغيّر أرقام عملٍ سابق مُصفّى.']);
        }

        $amount = (float) $segment->amount;
        $nights = $segment->nights;

        DB::transaction(function () use ($reservation, $segment, $amount, $nights) {
            // إزاحة الفترات اللاحقة (إن وُجدت) إلى الوراء بعدد ليالي الفترة المحذوفة
            $reservation->segments()
                ->where('id', '!=', $segment->id)
                ->where('start_date', '>=', $segment->end_date)
                ->get()
                ->each(function ($later) use ($nights) {
                    $later->update([
                        'start_date' => $later->start_date->copy()->subDays($nights),
                        'end_date'   => $later->end_date->copy()->subDays($nights),
                    ]);
                });

            $segment->delete();

            $reservation->check_out_date = $reservation->check_out_date->copy()->subDays($nights);
            if ($amount > 0) {
                $reservation->total_amount = max(0, round((float) $reservation->total_amount - $amount, 2));
            }
            $reservation->save();
            $reservation->refresh()->updatePaymentStatus();

            AuditLogService::log('update', $reservation, ['segment_amount' => $amount], [
                'action'     => 'renewal_segment_deleted',
                'segment_id' => $segment->id,
                'amount'     => $amount,
                'nights'     => $nights,
            ], auth()->user());
        });

        return redirect()->route('reservations.show', $reservation)
            ->with('success', 'تم حذف التجديد' . ($amount > 0 ? ' وخصم ' . number_format($amount, 0) . ' ر.ي من حساب النزيل' : ''));
    }

    /**
     * إضافة تجديد لم يُسجَّل في حينه (تصحيح: النزيل جدّد فعلياً أكثر مما يظهر في
     * السجل) — تُضاف كفترة تجديد جديدة تلي آخر فترة مسجَّلة مباشرةً، ويُمدَّد
     * تاريخ الخروج ويُزاد إجمالي الحجز بمبلغها. لحجزٍ نشط (مسجّل دخول) نمنع
     * التعارض مع حجزٍ قادم على نفس الغرفة قبل تمديد الخروج.
     */
    public function addSegment(Request $request, Reservation $reservation)
    {
        if (!in_array($reservation->status, ['checked_in', 'checked_out'])) {
            return back()->withErrors(['error' => 'يمكن إضافة تجديد فقط لحجزٍ نشط أو مغادر']);
        }

        $validated = $request->validate([
            'nights'          => 'required|integer|min:1|max:365',
            'price_per_night' => 'required|numeric|min:0',
        ], [
            'nights.required'          => 'عدد الليالي مطلوب',
            'nights.integer'           => 'عدد الليالي يجب أن يكون رقماً صحيحاً',
            'nights.min'               => 'عدد الليالي يجب أن يكون ليلة واحدة على الأقل',
            'price_per_night.required' => 'سعر الليلة مطلوب',
            'price_per_night.numeric'  => 'سعر الليلة يجب أن يكون رقماً',
        ]);

        $nights      = (int) $validated['nights'];
        $price       = (float) $validated['price_per_night'];
        $oldCheckOut = $reservation->check_out_date->copy();
        $newCheckOut = $oldCheckOut->copy()->addDays($nights);

        if ($reservation->status === 'checked_in') {
            $conflict = Reservation::findOverlap(
                $reservation->occupiedRoomIds(),
                $reservation->check_in_date,
                $newCheckOut,
                $reservation->id
            );
            if ($conflict) {
                $arrival     = Carbon::parse($conflict->check_in_date)->format('Y/m/d');
                $maxCheckout = Carbon::parse($conflict->check_in_date)
                    ->subDays(Reservation::TURNOVER_BUFFER_DAYS)->format('Y/m/d');
                return back()->withErrors(['nights' =>
                    'الغرفة محجوزة لنزيل قادم (' . ($conflict->guest?->full_name ?? '—') . ') يصل بتاريخ '
                    . $arrival . '، ويجب ترك يوم فاصل للتنظيف قبل وصوله. أقصى عدد ليالٍ يمكن إضافته يبقي الخروج قبل '
                    . $maxCheckout
                ]);
            }
        }

        $amount = round($nights * $price, 2);
        $shift  = app(\App\Services\ShiftService::class)->getActiveShift(auth()->user());

        DB::transaction(function () use ($reservation, $oldCheckOut, $newCheckOut, $nights, $price, $amount, $shift) {
            $reservation->check_out_date = $newCheckOut->toDateString();
            $reservation->total_amount   = round((float) $reservation->total_amount + $amount, 2);
            $reservation->save();
            $reservation->refresh()->updatePaymentStatus();

            app(\App\Services\ReservationSegmentService::class)->recordRenewal(
                $reservation, $oldCheckOut, $newCheckOut, $nights, $price, $amount, auth()->id(), $shift?->id
            );

            AuditLogService::log('update', $reservation, ['check_out_date' => $oldCheckOut->toDateString()], [
                'action'          => 'segment_added',
                'nights'          => $nights,
                'price_per_night' => $price,
                'amount'          => $amount,
                'check_out_date'  => $newCheckOut->toDateString(),
            ], auth()->user());
        });

        return redirect()->route('reservations.show', $reservation)
            ->with('success', 'تمت إضافة تجديد بمبلغ ' . number_format($amount, 0) . ' ر.ي، وتمديد تاريخ الخروج إلى ' . $newCheckOut->format('Y/m/d'));
    }

    /**
     * إعادة احتساب فترات الغرفة والإجمالي وفق الصيغة الحالية لحساب عدد الليالي
     * (حد الساعة 1 ظهراً بلحظتي الوصول والخروج الفعليتين) — دون تغيير أي تاريخ
     * أو وقت مسجَّل، فقط تصحيح كيفية تقسيم الفترات وحساب المجموع. يُستخدَم
     * لتصحيح حجوزات قديمة أُنشئت بصيغة حساب سابقة (فبقيت فتراتها/إجماليها غير
     * متطابقين مع الصيغة الحالية). يعمل للحجوزات النشطة والمغادرة معاً (بخلاف
     * "تعديل الحجز" العام المقصور على النشطة). الواجهة تعرض معاينة الفارق قبل
     * الضغط، فهذا الإجراء يُطبِّق مباشرةً بعد موافقة الموظف.
     */
    public function recomputeSegments(Reservation $reservation)
    {
        if (!in_array($reservation->status, ['checked_in', 'checked_out'])) {
            return back()->withErrors(['error' => 'إعادة الاحتساب متاحة فقط للحجوزات النشطة أو المغادرة']);
        }

        $billableNights = Reservation::billableNightsFor(
            $reservation->check_in_date, $reservation->check_out_date,
            $reservation->check_out_time, $reservation->check_in_time
        );

        // عند غياب سعر محفوظ صراحةً، نشتقّه من السعر الفعّال الحالي (الإجمالي ÷
        // الليالي) لا سعر الغرفة الافتراضي العام — فقد يكون هذا الحجز مسعَّراً
        // بسعر متفاوَض عليه يختلف عن سعر الغرفة الافتراضي (مثال: 50,000 بدل
        // السعر الافتراضي 45,000)، فلا يجوز إسقاط ذلك التفاوض عند إعادة الاحتساب.
        $firstNightPrice = $reservation->first_night_price !== null
            ? (float) $reservation->first_night_price
            : round((float) $reservation->gross_total / max(1, $reservation->nights), 2);
        $renewalPrice = $reservation->renewal_price_per_night !== null
            ? (float) $reservation->renewal_price_per_night
            : $firstNightPrice;

        $newGross       = round($firstNightPrice + max(0, $billableNights - 1) * $renewalPrice, 2);
        $discountAmount = $reservation->discountAmountFor($newGross);
        $newTotal       = round(max(0, round($newGross - $discountAmount, 2)) + $reservation->hotel_charges_total, 0);

        $old = $reservation->only(['total_amount', 'discount_amount']);

        DB::transaction(function () use ($reservation, $billableNights, $firstNightPrice, $renewalPrice, $newTotal, $discountAmount, $old) {
            $reservation->total_amount    = $newTotal;
            $reservation->discount_amount = $discountAmount;
            $reservation->save();
            $reservation->refresh()->updatePaymentStatus();

            app(\App\Services\ReservationSegmentService::class)
                ->rebuildFromCurrentPricing($reservation, $firstNightPrice, $renewalPrice, $billableNights, auth()->id());

            AuditLogService::log('update', $reservation, $old, [
                'action'       => 'segments_recomputed',
                'total_amount' => $newTotal,
                'nights'       => $billableNights,
            ], auth()->user());
        });

        return redirect()->route('reservations.show', $reservation)
            ->with('success', 'تمت إعادة احتساب فترات الغرفة والإجمالي بنجاح');
    }

    /**
     * إخفاء/إظهار تنبيه "تصحيح: إعادة احتساب الفترات والإجمالي" لهذا الحجز. يُستخدم
     * حين تكون فترات الحجز مقصودةً كما هي (سُعّرت يدوياً بأسعار تجديد مختلفة عبر
     * الزمن)، فلا يجوز توحيدها كلها بآخر سعر — والتنبيه المتكرّر مزعج ولا معنى له
     * لهذا الحجز. الإخفاء لا يمسّ أي أرقام؛ يُبدّل علماً فقط.
     */
    public function toggleRecomputeDismiss(Reservation $reservation)
    {
        $dismissed = !$reservation->recompute_dismissed;
        $reservation->update(['recompute_dismissed' => $dismissed]);

        AuditLogService::log('update', $reservation, ['recompute_dismissed' => !$dismissed], [
            'recompute_dismissed' => $dismissed,
        ], auth()->user());

        return back()->with('success', $dismissed
            ? 'تم إخفاء تنبيه إعادة الاحتساب لهذا الحجز — فتراته محفوظة كما هي'
            : 'تم إعادة تفعيل تنبيه إعادة الاحتساب لهذا الحجز');
    }

    /**
     * تعديل تاريخ ووقت الوصول والمغادرة معاً (تصحيح: تغيّر الموعد المتّفق عليه مع
     * النزيل) — يُعيد احتساب عدد الليالي والإجمالي وفق أسعار الحجز المحفوظة
     * (سعر الليلة الأولى وسعر التجديد) بناءً على التواريخ/الأوقات الجديدة، ويُعيد
     * بناء فترات الغرفة بالكامل. لا يمسّ أي دفعة مسجَّلة. يعمل للحجوزات النشطة
     * والمغادرة معاً (بخلاف "تعديل الحجز" العام المقصور على النشطة)، ولحجزٍ نشط
     * يمنع التعارض مع حجزٍ قادم على نفس الغرفة إن اتُّسِعت مدة الإقامة.
     */
    public function updateStayDates(Request $request, Reservation $reservation)
    {
        if (!in_array($reservation->status, ['checked_in', 'checked_out'])) {
            return back()->withErrors(['error' => 'تعديل تاريخ الإقامة متاح فقط للحجوزات النشطة أو المغادرة']);
        }

        $validated = $request->validate([
            'check_in_date'  => 'required|date',
            'check_in_time'  => 'nullable',
            'check_out_date' => 'required|date|after_or_equal:check_in_date',
            'check_out_time' => 'nullable',
        ], [
            'check_in_date.required'     => 'تاريخ الوصول مطلوب',
            'check_out_date.required'    => 'تاريخ المغادرة مطلوب',
            'check_out_date.after_or_equal' => 'تاريخ المغادرة لا يمكن أن يكون قبل تاريخ الوصول',
        ]);

        $newCheckOut = Carbon::parse($validated['check_out_date']);

        if ($reservation->status === 'checked_in' && $newCheckOut->gt($reservation->check_out_date)) {
            $conflict = Reservation::findOverlap(
                $reservation->occupiedRoomIds(),
                $validated['check_in_date'],
                $newCheckOut,
                $reservation->id
            );
            if ($conflict) {
                $arrival     = Carbon::parse($conflict->check_in_date)->format('Y/m/d');
                $maxCheckout = Carbon::parse($conflict->check_in_date)
                    ->subDays(Reservation::TURNOVER_BUFFER_DAYS)->format('Y/m/d');
                return back()->withErrors(['check_out_date' =>
                    'الغرفة محجوزة لنزيل قادم (' . ($conflict->guest?->full_name ?? '—') . ') يصل بتاريخ '
                    . $arrival . '، ويجب ترك يوم فاصل للتنظيف قبل وصوله. أقصى تاريخ خروج متاح: '
                    . $maxCheckout
                ])->withInput();
            }
        }

        $newBillableNights = Reservation::billableNightsFor(
            $validated['check_in_date'], $validated['check_out_date'],
            $validated['check_out_time'] ?? null, $validated['check_in_time'] ?? null
        );

        // عند غياب سعر محفوظ صراحةً، نشتقّه من السعر الفعّال الحالي (الإجمالي ÷
        // الليالي) لا سعر الغرفة الافتراضي العام — فقد يكون هذا الحجز مسعَّراً
        // بسعر متفاوَض عليه يختلف عن سعر الغرفة الافتراضي (مثال: 50,000 بدل
        // السعر الافتراضي 45,000)، فلا يجوز إسقاط ذلك التفاوض عند إعادة الاحتساب.
        $firstNightPrice = $reservation->first_night_price !== null
            ? (float) $reservation->first_night_price
            : round((float) $reservation->gross_total / max(1, $reservation->nights), 2);
        $renewalPrice = $reservation->renewal_price_per_night !== null
            ? (float) $reservation->renewal_price_per_night
            : $firstNightPrice;

        $newGross       = round($firstNightPrice + max(0, $newBillableNights - 1) * $renewalPrice, 2);
        $discountAmount = $reservation->discountAmountFor($newGross);
        $newTotal       = round(max(0, round($newGross - $discountAmount, 2)) + $reservation->hotel_charges_total, 0);

        $old = $reservation->only(['check_in_date', 'check_in_time', 'check_out_date', 'check_out_time', 'total_amount']);

        DB::transaction(function () use ($reservation, $validated, $newBillableNights, $firstNightPrice, $renewalPrice, $newTotal, $discountAmount, $old) {
            $reservation->check_in_date   = $validated['check_in_date'];
            $reservation->check_in_time   = $this->nullIfEmpty($validated['check_in_time'] ?? null);
            $reservation->check_out_date  = $validated['check_out_date'];
            $reservation->check_out_time  = $this->nullIfEmpty($validated['check_out_time'] ?? null);
            $reservation->total_amount    = $newTotal;
            $reservation->discount_amount = $discountAmount;
            $reservation->save();
            $reservation->refresh()->updatePaymentStatus();

            app(\App\Services\ReservationSegmentService::class)
                ->rebuildFromCurrentPricing($reservation, $firstNightPrice, $renewalPrice, $newBillableNights, auth()->id());

            AuditLogService::log('update', $reservation, $old, [
                'action'          => 'stay_dates_updated',
                'check_in_date'   => $reservation->check_in_date->toDateString(),
                'check_in_time'   => $reservation->check_in_time,
                'check_out_date'  => $reservation->check_out_date->toDateString(),
                'check_out_time'  => $reservation->check_out_time,
                'total_amount'    => $newTotal,
                'nights'          => $newBillableNights,
            ], auth()->user());
        });

        return redirect()->route('reservations.show', $reservation)
            ->with('success', 'تم تعديل تاريخ الإقامة بنجاح — الإجمالي الجديد: ' . number_format($newTotal, 0) . ' ر.ي');
    }

    private function nullIfEmpty(mixed $value): mixed
    {
        return ($value === '' || $value === null) ? null : $value;
    }
}
