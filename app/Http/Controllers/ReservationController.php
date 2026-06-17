<?php
namespace App\Http\Controllers;

use App\Models\Companion;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
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

        if ($request->filled('created_by')) {
            $query->where('created_by', $request->created_by);
        }

        $reservations = $query->latest()->paginate(25)->withQueryString();
        $staff = User::where('is_active', true)->orderBy('name')->get();

        return view('reservations.index', compact('reservations', 'staff'));
    }

    public function show(Reservation $reservation)
    {
        $reservation->load(['guest', 'room.roomType', 'companions', 'payments', 'extraCharges', 'roomInspections.images', 'createdBy', 'adminApproval']);
        $availableRooms = $reservation->status === 'checked_in'
            ? Room::with('roomType')->where('status', 'available')->orderBy('floor')->orderBy('room_number')->get()
            : collect();
        return view('reservations.show', compact('reservation', 'availableRooms'));
    }

    public function edit(Reservation $reservation)
    {
        if (!in_array($reservation->status, ['confirmed', 'checked_in'])) {
            return back()->with('error', 'لا يمكن تعديل هذا الحجز في حالته الحالية');
        }

        $reservation->load(['guest', 'room.roomType', 'companions']);

        return view('reservations.edit', compact('reservation'));
    }

    public function update(Request $request, Reservation $reservation)
    {
        if (!in_array($reservation->status, ['confirmed', 'checked_in'])) {
            return back()->with('error', 'لا يمكن تعديل هذا الحجز في حالته الحالية');
        }

        $validated = $request->validate([
            'check_in_date'              => 'required|date',
            'check_out_date'             => 'required|date|after:check_in_date',
            'purpose'                    => 'nullable|string|max:255',
            'origin'                     => 'nullable|string|max:255',
            'notes'                      => 'nullable|string|max:1000',
            // Guest fields
            'guest_full_name'            => 'required|string|max:255',
            'guest_nationality'          => 'nullable|string|max:100',
            'guest_occupation'           => 'nullable|string|max:100',
            'guest_id_type'              => 'nullable|string|max:50',
            'guest_id_number'            => 'nullable|string|max:50',
            'guest_id_issuer'            => 'nullable|string|max:100',
            'guest_id_issue_date'        => 'nullable|date',
            'guest_phone'                => 'nullable|string|max:30',
            // Companions
            'companions'                 => 'nullable|array',
            'companions.*.id'            => 'nullable|integer',
            'companions.*.full_name'     => 'required_with:companions|string|max:255',
            'companions.*.nationality'   => 'nullable|string|max:100',
            'companions.*.id_type'       => 'nullable|string|max:50',
            'companions.*.id_number'     => 'nullable|string|max:50',
            'companions.*.relationship'  => 'nullable|string|max:50',
            'companions.*.delete'        => 'nullable|boolean',
        ], [
            'check_in_date.required'     => 'تاريخ الدخول مطلوب',
            'check_in_date.date'         => 'تاريخ الدخول غير صالح',
            'check_out_date.required'    => 'تاريخ الخروج مطلوب',
            'check_out_date.date'        => 'تاريخ الخروج غير صالح',
            'check_out_date.after'       => 'تاريخ الخروج يجب أن يكون بعد تاريخ الدخول',
            'guest_full_name.required'   => 'اسم النزيل مطلوب',
        ]);

        $old = $reservation->toArray();

        // Update guest
        if ($reservation->guest) {
            $reservation->guest->update([
                'full_name'     => $validated['guest_full_name'],
                'nationality'   => $validated['guest_nationality'] ?? null,
                'occupation'    => $validated['guest_occupation'] ?? null,
                'id_type'       => $validated['guest_id_type'] ?? null,
                'id_number'     => $validated['guest_id_number'] ?? null,
                'id_issuer'     => $validated['guest_id_issuer'] ?? null,
                'id_issue_date' => $validated['guest_id_issue_date'] ?? null,
                'phone'         => $validated['guest_phone'] ?? null,
            ]);
        }

        // Update companions
        $submittedIds = [];
        foreach ($request->input('companions', []) as $comp) {
            if (!empty($comp['delete']) && !empty($comp['id'])) {
                Companion::where('id', $comp['id'])->where('reservation_id', $reservation->id)->delete();
                continue;
            }
            if (empty($comp['full_name'])) continue;

            $data = [
                'full_name'    => $comp['full_name'],
                'nationality'  => $comp['nationality'] ?? null,
                'id_type'      => $comp['id_type'] ?? null,
                'id_number'    => $comp['id_number'] ?? null,
                'relationship' => $comp['relationship'] ?? null,
            ];

            if (!empty($comp['id'])) {
                $existing = Companion::where('id', $comp['id'])->where('reservation_id', $reservation->id)->first();
                if ($existing) {
                    $existing->update($data);
                    $submittedIds[] = $existing->id;
                }
            } else {
                $created = $reservation->companions()->create($data);
                $submittedIds[] = $created->id;
            }
        }

        $nights = Carbon::parse($validated['check_in_date'])->diffInDays($validated['check_out_date']);
        $newTotal = $nights * $reservation->room->roomType->base_price;

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

        $extraNights = $reservation->check_out_date->diffInDays($validated['new_check_out_date']);
        $pricePerNight = $reservation->room->roomType->base_price;
        $extraAmount  = $extraNights * $pricePerNight;

        $old = $reservation->only(['check_out_date', 'total_amount']);

        $reservation->update([
            'check_out_date' => $validated['new_check_out_date'],
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

    public function arrive(Reservation $reservation)
    {
        if ($reservation->status !== 'confirmed') {
            return back()->with('error', 'لا يمكن تسجيل الوصول إلا للحجوزات المؤكدة');
        }

        $old = ['status' => $reservation->status];

        $reservation->update(['status' => 'checked_in']);

        $reservation->room?->update(['status' => 'occupied']);
        if ($reservation->linkedRoom) {
            $reservation->linkedRoom->update(['status' => 'occupied']);
        }

        AuditLogService::log('update', $reservation, $old, ['status' => 'checked_in'], auth()->user());

        return redirect()->route('reservations.show', $reservation)
            ->with('success', 'تم تسجيل وصول النزيل بنجاح — الغرفة أصبحت مشغولة');
    }

    public function transferRoom(Request $request, Reservation $reservation)
    {
        if ($reservation->status !== 'checked_in') {
            return back()->with('error', 'تغيير الغرفة متاح فقط للحجوزات النشطة (مسجل دخول)');
        }

        $validated = $request->validate([
            'new_room_id' => 'required|exists:rooms,id|different:' . $reservation->room_id,
            'notes'       => 'nullable|string|max:500',
        ], [
            'new_room_id.required'  => 'يرجى اختيار الغرفة الجديدة',
            'new_room_id.different' => 'الغرفة الجديدة يجب أن تختلف عن الغرفة الحالية',
        ]);

        $newRoom = Room::findOrFail($validated['new_room_id']);

        if ($newRoom->status !== 'available') {
            return back()->withErrors(['new_room_id' => 'الغرفة المختارة غير متاحة']);
        }

        $oldRoom = $reservation->room;
        $old = ['room_id' => $reservation->room_id];

        // Move guest to new room
        $reservation->update([
            'room_id' => $newRoom->id,
            'notes'   => $reservation->notes
                            ? $reservation->notes . "\n[نقل من غرفة {$oldRoom->room_number} إلى {$newRoom->room_number}]"
                            : "[نقل من غرفة {$oldRoom->room_number} إلى {$newRoom->room_number}]" . ($validated['notes'] ? ': ' . $validated['notes'] : ''),
        ]);

        // New room becomes occupied
        $newRoom->update(['status' => 'occupied']);

        // Old room goes to inspection
        $oldRoom->update(['status' => 'under_inspection']);

        AuditLogService::log('update', $reservation, $old, ['room_id' => $newRoom->id, 'action' => 'room_transfer'], auth()->user());

        return redirect()->route('reservations.show', $reservation)
            ->with('success', "تم نقل النزيل من غرفة {$oldRoom->room_number} إلى غرفة {$newRoom->room_number} — الغرفة القديمة في وضع الفحص");
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
