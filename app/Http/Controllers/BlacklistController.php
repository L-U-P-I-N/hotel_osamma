<?php
namespace App\Http\Controllers;

use App\Models\Guest;
use App\Services\AuditLogService;
use Illuminate\Http\Request;

class BlacklistController extends Controller
{
    public function index(Request $request)
    {
        $query = Guest::blacklisted();
        if ($request->filled('search')) {
            $query->where('full_name', 'like', '%' . $request->search . '%');
        }
        $guests = $query->latest('blacklisted_at')->paginate(25)->withQueryString();
        return view('blacklist.index', compact('guests'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'guest_id' => 'required|exists:guests,id',
            'reason' => 'required|string',
        ]);

        $guest = Guest::findOrFail($request->guest_id);
        $old = $guest->toArray();
        $guest->update([
            'is_blacklisted' => true,
            'blacklist_reason' => $request->reason,
            'blacklisted_at' => now(),
        ]);

        AuditLogService::log('update', $guest, ['is_blacklisted' => false], ['is_blacklisted' => true, 'reason' => $request->reason]);

        return back()->with('success', 'تمت إضافة النزيل للقائمة السوداء');
    }

    public function remove(Guest $guest)
    {
        $guest->update([
            'is_blacklisted' => false,
            'blacklist_reason' => null,
            'blacklisted_at' => null,
        ]);

        AuditLogService::log('update', $guest, ['is_blacklisted' => true], ['is_blacklisted' => false]);

        return back()->with('success', 'تمت إزالة النزيل من القائمة السوداء');
    }
}
