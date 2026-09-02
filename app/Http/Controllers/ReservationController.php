<?php
namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Room;
use App\Services\AuditLogService;
use App\Services\PricingService;
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

        $reservations = $query->latest()->paginate(25)->withQueryString();

        return view('reservations.index', compact('reservations'));
    }

    public function show(Reservation $reservation)
    {
        $reservation->load([
            'guest', 'room.roomType', 'companions', 'payments', 'extraCharges',
            'roomInspections.images', 'createdBy', 'adminApproval', 'discountedBy',
        ]);

        $nightlyPrice       = PricingService::nightlyPriceFor($reservation);
        $maxDiscountAmount  = PricingService::maxDiscountAmount($reservation);
        $maxDiscountPercent = PricingService::maxDiscountPercent();

        return view('reservations.show', compact(
            'reservation', 'nightlyPrice', 'maxDiscountAmount', 'maxDiscountPercent'
        ));
    }

    public function edit(Reservation $reservation)
    {
        if (!in_array($reservation->status, ['confirmed', 'checked_in'])) {
            return back()->with('error', 'لا يمكن تعديل هذا الحجز في حالته الحالية');
        }

        $reservation->load(['guest', 'room.roomType']);
        $rooms = Room::with('roomType')
            ->where(fn($q) => $q->available()->orWhere('id', $reservation->room_id))
            ->orderBy('floor')->orderBy('room_number')->get();

        $roomType     = $reservation->room->roomType;
        $multiplier   = PricingService::unitMultiplier($reservation->room, $reservation->suite_booking_type);
        $unitPrice    = round(PricingService::nightlyPriceFor($reservation) / max(1, $multiplier), 2);
        $canEditPrice = auth()->user()->can(PricingService::PRICE_OVERRIDE_PERMISSION);

        return view('reservations.edit', compact(
            'reservation', 'rooms', 'roomType', 'multiplier', 'unitPrice', 'canEditPrice'
        ));
    }

    public function update(Request $request, Reservation $reservation)
    {
        if (!in_array($reservation->status, ['confirmed', 'checked_in'])) {
            return back()->with('error', 'لا يمكن تعديل هذا الحجز في حالته الحالية');
        }

        $validated = $request->validate([
            'check_in_date'  => 'required|date',
            'check_out_date' => 'required|date|after:check_in_date',
            'nightly_price'  => 'nullable|numeric|min:0',
            'purpose'        => 'nullable|string|max:255',
            'origin'         => 'nullable|string|max:255',
            'notes'          => 'nullable|string|max:1000',
        ], [
            'check_in_date.required'   => 'تاريخ الدخول مطلوب',
            'check_in_date.date'       => 'تاريخ الدخول غير صالح',
            'check_out_date.required'  => 'تاريخ الخروج مطلوب',
            'check_out_date.date'      => 'تاريخ الخروج غير صالح',
            'check_out_date.after'     => 'تاريخ الخروج يجب أن يكون بعد تاريخ الدخول',
            'purpose.max'              => 'الغرض لا يتجاوز 255 حرف',
            'origin.max'               => 'جهة القدوم لا تتجاوز 255 حرف',
            'notes.max'                => 'الملاحظات لا تتجاوز 1000 حرف',
            'nightly_price.numeric'    => 'سعر الليلة يجب أن يكون رقماً',
        ]);

        // السعر يُحسم في الخادم عبر PricingService: الموظف يرسل سعر الليلة فقط،
        // والإجمالي لا يأتي من المتصفح إطلاقاً.
        $multiplier   = PricingService::unitMultiplier($reservation->room, $reservation->suite_booking_type);
        $currentUnit  = round(PricingService::nightlyPriceFor($reservation) / max(1, $multiplier), 2);
        $requestedUnit = $validated['nightly_price'] ?? $currentUnit;

        $nightlyPrice = PricingService::resolveNightlyPrice(
            $reservation->room,
            $reservation->suite_booking_type,
            $requestedUnit,
            auth()->user()
        );

        $nights   = Carbon::parse($validated['check_in_date'])->diffInDays($validated['check_out_date']);
        $newTotal = round($nightlyPrice * $nights, 2) - (float) $reservation->discount_amount;

        $old = $reservation->toArray();

        $reservation->update([
            'check_in_date'  => $validated['check_in_date'],
            'check_out_date' => $validated['check_out_date'],
            'purpose'        => $validated['purpose'] ?? null,
            'origin'         => $validated['origin'] ?? null,
            'notes'          => $validated['notes'] ?? null,
            'nightly_price'  => $nightlyPrice,
            'total_amount'   => max(0, $newTotal),
        ]);

        $reservation->refresh()->updatePaymentStatus();

        AuditLogService::log('update', $reservation, $old, $reservation->fresh()->toArray(), auth()->user());

        return redirect()->route('reservations.show', $reservation)->with('success', 'تم تحديث الحجز بنجاح');
    }

    public function renew(Request $request, Reservation $reservation)
    {
        if ($reservation->status !== 'checked_in') {
            return back()->withErrors(['error' => 'لا يمكن تجديد إلا الحجوزات النشطة (مسجل دخول)']);
        }

        $validated = $request->validate([
            'new_check_out_date' => 'required|date|after:' . $reservation->check_out_date->format('Y-m-d'),
            'advance_payment'    => 'nullable|numeric|min:0',
            'payment_method'     => 'nullable|in:cash,pos,bank_transfer',
            'currency'           => 'nullable|in:YER,SAR,USD',
            'notes'              => 'nullable|string|max:500',
        ], [
            'new_check_out_date.required' => 'تاريخ الخروج الجديد مطلوب',
            'new_check_out_date.after'    => 'يجب أن يكون تاريخ الخروج الجديد بعد التاريخ الحالي',
        ]);

        $extraNights   = $reservation->check_out_date->diffInDays($validated['new_check_out_date']);
        // التجديد يُسعَّر بنفس السعر المتفق عليه في الحجز، لا بسعر جديد يختاره الموظف
        $pricePerNight = PricingService::nightlyPriceFor($reservation);
        $extraAmount   = round($extraNights * $pricePerNight, 2);

        $old = $reservation->only(['check_out_date', 'total_amount']);

        $reservation->update([
            'check_out_date' => $validated['new_check_out_date'],
            'nightly_price'  => $pricePerNight,
            'total_amount'   => $reservation->total_amount + $extraAmount,
            'notes'          => $reservation->notes
                                    ? $reservation->notes . "\n[تجديد +{$extraNights} ليلة]"
                                    : "[تجديد +{$extraNights} ليلة]" . ($validated['notes'] ? ': ' . $validated['notes'] : ''),
        ]);

        if (!empty($validated['advance_payment']) && $validated['advance_payment'] > 0) {
            \App\Models\Payment::create([
                'reservation_id' => $reservation->id,
                'received_by'    => auth()->id(),
                'amount'         => $validated['advance_payment'],
                'currency'       => $validated['currency'] ?? 'YER',
                'method'         => $validated['payment_method'] ?? 'cash',
                'payment_date'   => now(),
                'type'           => 'reservation',
            ]);
            $reservation->increment('paid_amount', $validated['advance_payment']);
            $reservation->refresh()->updatePaymentStatus();
        }

        AuditLogService::log('update', $reservation, $old, [
            'check_out_date' => $validated['new_check_out_date'],
            'extra_nights'   => $extraNights,
            'extra_amount'   => $extraAmount,
        ], auth()->user());

        return redirect()->route('reservations.show', $reservation)
            ->with('success', "تم تجديد الإقامة بنجاح — تمديد {$extraNights} ليلة إضافية");
    }

    /**
     * منح خصم على الحجز — محكوم بصلاحية reservation.discount وبسقف النسبة
     * الذي يحدده المدير في إعدادات التسعير.
     */
    public function discount(Request $request, Reservation $reservation)
    {
        if (in_array($reservation->status, ['cancelled'])) {
            return back()->with('error', 'لا يمكن منح خصم على حجز ملغي');
        }

        $maxAllowed = PricingService::maxDiscountAmount($reservation);

        if ($maxAllowed <= 0) {
            $percent = PricingService::maxDiscountPercent();
            return back()->withErrors(['amount' => $percent <= 0
                ? 'الخصم موقوف — لم يحدد المدير سقفاً للخصم في إعدادات التسعير'
                : 'لا يوجد مبلغ متاح للخصم على هذا الحجز (تم استنفاد السقف أو لا يوجد رصيد متبقٍ)']);
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:' . $maxAllowed,
            'reason' => 'required|string|max:255',
        ], [
            'amount.required' => 'مبلغ الخصم مطلوب',
            'amount.numeric'  => 'مبلغ الخصم يجب أن يكون رقماً',
            'amount.min'      => 'مبلغ الخصم يجب أن يكون أكبر من صفر',
            'amount.max'      => 'أقصى خصم مسموح على هذا الحجز هو ' . number_format($maxAllowed, 2) . ' ر.ي',
            'reason.required' => 'سبب الخصم مطلوب',
            'reason.max'      => 'سبب الخصم لا يتجاوز 255 حرف',
        ]);

        $amount = round((float) $validated['amount'], 2);
        $old    = $reservation->only(['total_amount', 'discount_amount', 'discount_reason']);

        DB::transaction(function () use ($reservation, $amount, $validated) {
            // القفل يمنع تجاوز السقف عند طلبين متزامنين على نفس الحجز
            $locked = Reservation::whereKey($reservation->id)->lockForUpdate()->firstOrFail();

            $allowedNow = PricingService::maxDiscountAmount($locked);
            if ($amount > $allowedNow) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'amount' => 'أقصى خصم مسموح على هذا الحجز هو ' . number_format($allowedNow, 2) . ' ر.ي',
                ]);
            }

            $locked->update([
                'total_amount'    => max(0, round((float) $locked->total_amount - $amount, 2)),
                'discount_amount' => round((float) $locked->discount_amount + $amount, 2),
                'discount_reason' => $validated['reason'],
                'discounted_by'   => auth()->id(),
                'discounted_at'   => now(),
            ]);

            $locked->refresh()->updatePaymentStatus();
        });

        $reservation->refresh();

        AuditLogService::log('discount', $reservation, $old, [
            'discount_amount' => $amount,
            'discount_reason' => $validated['reason'],
            'total_amount'    => $reservation->total_amount,
        ], auth()->user());

        return back()->with('success', 'تم منح خصم بمبلغ ' . number_format($amount, 2) . ' ر.ي');
    }

    public function cancel(Reservation $reservation)
    {
        if ($reservation->status === 'checked_out') {
            return back()->with('error', 'لا يمكن إلغاء حجز مكتمل (تسجيل الخروج تم)');
        }
        if ($reservation->status === 'cancelled') {
            return back()->with('error', 'الحجز ملغي مسبقاً');
        }

        $old = ['status' => $reservation->status];
        $reservation->update(['status' => 'cancelled']);

        if (in_array($reservation->room->status, ['occupied', 'reserved'])) {
            $reservation->room->update(['status' => 'available']);
        }

        AuditLogService::log('update', $reservation, $old, ['status' => 'cancelled'], auth()->user());

        return redirect()->route('reservations.show', $reservation)->with('success', 'تم إلغاء الحجز بنجاح');
    }
}
