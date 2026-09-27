<?php

namespace App\Http\Controllers;

use App\Models\HotelNote;
use App\Services\AuditLogService;
use Illuminate\Http\Request;

/**
 * الملاحظات العامة على الفندق — تُدار من أعلى صفحة الحجوزات عبر JSON، فلا
 * يحتاج الموظف لمغادرة الصفحة ليكتب تنبيهاً لمن بعده.
 */
class HotelNoteController extends Controller
{
    /** الملاحظات القائمة (والمنتهية عند طلبها) — تقرأها لوحة أعلى الصفحة. */
    public function index(Request $request)
    {
        return response()->json([
            'notes' => $this->payload($request->boolean('include_resolved')),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'body'      => 'required|string|max:1000',
            'type'      => 'required|in:' . implode(',', array_keys(HotelNote::TYPES)),
            'is_pinned' => 'nullable|boolean',
        ], [
            'body.required' => 'نصّ الملاحظة مطلوب',
            'body.max'      => 'الملاحظة طويلة جداً (1000 حرف كحد أقصى)',
            'type.in'       => 'نوع الملاحظة غير معروف',
        ]);

        $note = HotelNote::create([
            'type'       => $validated['type'],
            'body'       => $validated['body'],
            'is_pinned'  => $request->boolean('is_pinned'),
            'created_by' => auth()->id(),
        ]);

        AuditLogService::log('create', $note, null, [
            'action' => 'hotel_note_added', 'type' => $note->type,
        ], auth()->user());

        return response()->json([
            'success' => true,
            'message' => 'تمت إضافة الملاحظة العامة',
            'notes'   => $this->payload($request->boolean('include_resolved')),
        ]);
    }

    public function update(Request $request, HotelNote $note)
    {
        $validated = $request->validate([
            'body'      => 'nullable|string|max:1000',
            'type'      => 'nullable|in:' . implode(',', array_keys(HotelNote::TYPES)),
            'is_pinned' => 'nullable|boolean',
            'resolved'  => 'nullable|boolean',
        ]);

        if (array_key_exists('body', $validated) && $validated['body'] !== null && trim($validated['body']) !== '') {
            $note->body = trim($validated['body']);
        }
        if (!empty($validated['type'])) {
            $note->type = $validated['type'];
        }
        if ($request->has('is_pinned')) {
            $note->is_pinned = $request->boolean('is_pinned');
        }
        if ($request->has('resolved')) {
            $resolved = $request->boolean('resolved');
            $note->resolved_at = $resolved ? now() : null;
            $note->resolved_by = $resolved ? auth()->id() : null;
        }
        $note->save();

        AuditLogService::log('update', $note, null, [
            'action' => 'hotel_note_updated', 'note_id' => $note->id,
        ], auth()->user());

        return response()->json([
            'success' => true,
            'message' => $request->has('resolved')
                ? ($note->resolved_at ? 'تم تعليم الملاحظة كمنتهية' : 'أُعيد فتح الملاحظة')
                : 'تم تعديل الملاحظة',
            'notes'   => $this->payload($request->boolean('include_resolved')),
        ]);
    }

    public function destroy(Request $request, HotelNote $note)
    {
        $note->delete();

        AuditLogService::log('delete', $note, null, [
            'action' => 'hotel_note_deleted', 'note_id' => $note->id,
        ], auth()->user());

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الملاحظة',
            'notes'   => $this->payload($request->boolean('include_resolved')),
        ]);
    }

    /** شكل الملاحظات الذي تقرأه اللوحة: القائمة + عدّاد القائم منها. */
    private function payload(bool $includeResolved = false): array
    {
        $notes = HotelNote::with('createdBy')
            ->when(!$includeResolved, fn ($q) => $q->open())
            ->boardOrder()
            ->limit(50)
            ->get();

        $open = $notes->whereNull('resolved_at');

        return [
            'items' => $notes->map(fn (HotelNote $n) => [
                'id'         => $n->id,
                'type'       => $n->type,
                'type_label' => $n->type_label,
                'color'      => $n->color,
                'body'       => $n->body,
                'pinned'     => (bool) $n->is_pinned,
                'resolved'   => $n->is_resolved,
                'author'     => $n->createdBy?->name,
                'created_at' => $n->created_at?->format('d/m/Y H:i'),
            ])->values()->all(),
            'open_count'   => $open->count(),
            'urgent_count' => $open->where('type', 'urgent')->count(),
        ];
    }
}
