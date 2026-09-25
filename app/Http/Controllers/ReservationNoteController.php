<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\ReservationNote;
use App\Services\AuditLogService;
use Illuminate\Http\Request;

/**
 * ملاحظات فورية على الحجز تُدار من جدول الحجوزات مباشرةً (JSON)، فلا يضطر
 * الموظف لفتح صفحة التفاصيل ليكتب "على النزيل التزام قبل المغادرة".
 */
class ReservationNoteController extends Controller
{
    /** ملاحظات حجز واحد — تُقرأ عند فتح نافذة الملاحظات في الجدول. */
    public function index(Reservation $reservation)
    {
        return response()->json([
            'notes' => $this->payload($reservation),
        ]);
    }

    public function store(Request $request, Reservation $reservation)
    {
        $validated = $request->validate([
            'body' => 'required|string|max:1000',
            'type' => 'required|in:' . implode(',', array_keys(ReservationNote::TYPES)),
        ], [
            'body.required' => 'نصّ الملاحظة مطلوب',
            'body.max'      => 'الملاحظة طويلة جداً (1000 حرف كحد أقصى)',
            'type.in'       => 'نوع الملاحظة غير معروف',
        ]);

        $note = $reservation->quickNotes()->create([
            'type'       => $validated['type'],
            'body'       => $validated['body'],
            'created_by' => auth()->id(),
        ]);

        AuditLogService::log('update', $reservation, null, [
            'action' => 'note_added', 'note_id' => $note->id, 'type' => $note->type,
        ], auth()->user());

        return response()->json([
            'success' => true,
            'message' => 'تمت إضافة الملاحظة',
            'notes'   => $this->payload($reservation->refresh()),
        ]);
    }

    public function update(Request $request, Reservation $reservation, ReservationNote $note)
    {
        abort_unless($note->reservation_id === $reservation->id, 404);

        $validated = $request->validate([
            'body' => 'nullable|string|max:1000',
            'type' => 'nullable|in:' . implode(',', array_keys(ReservationNote::TYPES)),
            // تعليم الملاحظة محلولة: تبقى في السجل ولا تُلوّن الأيقونة بعدها
            'resolved' => 'nullable|boolean',
        ]);

        if (array_key_exists('body', $validated) && $validated['body'] !== null) {
            $note->body = $validated['body'];
        }
        if (!empty($validated['type'])) {
            $note->type = $validated['type'];
        }
        if ($request->has('resolved')) {
            $resolved = $request->boolean('resolved');
            $note->resolved_at = $resolved ? now() : null;
            $note->resolved_by = $resolved ? auth()->id() : null;
        }
        $note->save();

        AuditLogService::log('update', $reservation, null, [
            'action' => 'note_updated', 'note_id' => $note->id,
        ], auth()->user());

        return response()->json([
            'success' => true,
            'message' => $request->has('resolved')
                ? ($note->resolved_at ? 'تم تعليم الملاحظة كمنتهية' : 'أُعيد فتح الملاحظة')
                : 'تم تعديل الملاحظة',
            'notes'   => $this->payload($reservation->refresh()),
        ]);
    }

    public function destroy(Reservation $reservation, ReservationNote $note)
    {
        abort_unless($note->reservation_id === $reservation->id, 404);

        $note->delete();

        AuditLogService::log('update', $reservation, null, [
            'action' => 'note_deleted', 'note_id' => $note->id,
        ], auth()->user());

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الملاحظة',
            'notes'   => $this->payload($reservation->refresh()),
        ]);
    }

    /** شكل الملاحظات الذي يقرأه الجدول: القائمة + ما يلوّن الأيقونة. */
    private function payload(Reservation $reservation): array
    {
        $notes = $reservation->quickNotes()->with('createdBy')->get();
        $open  = $notes->whereNull('resolved_at');

        return [
            'items' => $notes->map(fn(ReservationNote $n) => [
                'id'         => $n->id,
                'type'       => $n->type,
                'type_label' => $n->type_label,
                'color'      => $n->color,
                'body'       => $n->body,
                'resolved'   => $n->is_resolved,
                'author'     => $n->createdBy?->name,
                'created_at' => $n->created_at?->format('d/m/Y H:i'),
            ])->values()->all(),
            'open_count' => $open->count(),
            // أشدّ لون بين الملاحظات القائمة — الأحمر يسبق البرتقالي ثم الأزرق
            'color'   => $open->contains(fn($n) => $n->color === 'red') ? 'red'
                : ($open->contains(fn($n) => $n->color === 'amber') ? 'amber'
                : ($open->isNotEmpty() ? 'blue' : 'none')),
            'preview' => $open->first()?->body,
        ];
    }
}
