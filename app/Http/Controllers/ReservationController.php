<?php
namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Room;
use App\Services\AuditLogService;
use Carbon\Carbon;
use Illuminate\Http\Request;

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
        $reservation->load(['guest', 'room.roomType', 'companions', 'payments', 'extraCharges', 'roomInspections.images', 'createdBy', 'adminApproval']);
        return view('reservations.show', compact('reservation'));
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

        return view('reservations.edit', compact('reservation', 'rooms'));
    }

    public function update(Request $request, Reservation $reservation)
    {
        if (!in_array($reservation->status, ['confirmed', 'checked_in'])) {
            return back()->with('error', 'لا يمكن تعديل هذا الحجز في حالته الحالية');
        }

        $validated = $request->validate([
            'check_in_date'  => 'required|date',
            'check_out_date' => 'required|date|after:check_in_date',
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
        ]);

        $nights = Carbon::parse($validated['check_in_date'])->diffInDays($validated['check_out_date']);
        $pricePerNight = $reservation->room->roomType->base_price_per_night;
        $newTotal = $nights * $pricePerNight;

        $old = $reservation->toArray();

        $reservation->update([
            'check_in_date'  => $validated['check_in_date'],
            'check_out_date' => $validated['check_out_date'],
            'purpose'        => $validated['purpose'] ?? null,
            'origin'         => $validated['origin'] ?? null,
            'notes'          => $validated['notes'] ?? null,
            'total_amount'   => $newTotal,
        ]);

        AuditLogService::log('update', $reservation, $old, $reservation->fresh()->toArray(), auth()->user());

        return redirect()->route('reservations.show', $reservation)->with('success', 'تم تحديث الحجز بنجاح');
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
