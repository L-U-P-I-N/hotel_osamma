<?php
namespace App\Http\Controllers;

use App\Exceptions\BlacklistedException;
use App\Models\Guest;
use App\Models\Hotel;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use App\Models\Reservation;
use App\Helpers\StorageHelper;
use App\Services\CheckInService;
use App\Services\GovernmentExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CheckInController extends Controller
{
    public function __construct(
        private CheckInService $checkInService,
        private GovernmentExportService $exportService
    ) {}

    public function create(Request $request)
    {
        $mode = 'checkin';

        $availableRooms = Room::with('roomType', 'linkedRoom')
            ->available()
            ->orderBy('floor')
            ->orderBy('room_number')
            ->get();

        // Build a map of linked-room availability for the JS wizard
        $linkedAvailability = [];
        foreach ($availableRooms as $room) {
            if ($room->linked_room_id) {
                $linkedAvailability[$room->id] = [
                    'linked_id'        => $room->linked_room_id,
                    'linked_available' => $room->isLinkedRoomAvailable(),
                    'linked_number'    => $room->linkedRoom?->room_number,
                    'sub_type'         => $room->room_sub_type,
                    'is_always_linked' => (bool)$room->is_always_linked,
                ];
            }
        }

        $admins = User::role('admin')->where('is_active', true)->get();
        $nationalities = $this->getNationalities();

        // Filter display rooms: hide suite_b when its suite_a pair is available
        $linkedBIds = $availableRooms->where('room_sub_type', 'suite_a')
            ->pluck('linked_room_id')->filter()->toArray();
        $displayRooms = $availableRooms->filter(
            fn($r) => !($r->room_sub_type === 'suite_b' && in_array($r->id, $linkedBIds))
        );
        $floors = $displayRooms->pluck('floor')->unique()->sort()->values();

        return view('checkin.create', compact('availableRooms', 'displayRooms', 'floors', 'linkedAvailability', 'admins', 'nationalities', 'mode'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'full_name'   => 'required|string|max:255',
            'occupation'  => 'nullable|string|max:255',
            'phone'       => 'required|string|max:30',
            'id_type'     => 'required|in:passport,national_id,residence',
            'id_number'   => 'required|string',
            'id_image'    => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'nationality' => 'nullable|string|max:100',
            'origin'      => 'nullable|string|max:255',
            'purpose'     => 'nullable|string|max:255',
            'room_id'         => 'required|exists:rooms,id',
            'check_in_date'   => 'required|date',
            'check_in_time'   => 'nullable|regex:/^\d{2}:\d{2}$/',
            'check_out_date'  => 'required|date|after:check_in_date',
            'payment_status'  => 'required|in:unpaid,partial,paid',
            'currency'        => 'nullable|in:YER',
            'bank_receipt' => 'required_if:payment_method,bank_transfer|nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'companions.*.full_name' => 'required_with:companions.*.relationship|string',
            'companions.*.relationship' => 'nullable|in:wife,son,daughter,brother,sister,father,mother,other',
            'companions.*.nationality' => 'required_with:companions.*.full_name|nullable|string|max:100',
            'companions.*.id_image'    => 'required_with:companions.*.full_name|nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'companions.*.marriage_doc' => 'required_if:companions.*.relationship,wife|nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'suite_booking_type'        => 'nullable|in:a_only,b_only,both',
        ]);

        try {
            $data = $request->except(['_token']);
            $data['paid_amount'] = $request->input('paid_amount', 0);
            $data['total_amount'] = $request->input('total_amount', 0);
            $data['payment_method'] = $request->input('payment_method', 'cash');
            $data['currency'] = 'YER';
            if ($request->filled('payment_notes')) {
                $data['payment_notes'] = $request->input('payment_notes');
            }

            if ($request->hasFile('id_image')) {
                $data['id_image'] = $request->file('id_image');
            }
            if ($request->hasFile('bank_receipt')) {
                $data['bank_receipt'] = $request->file('bank_receipt');
            }

            if ($request->has('companions')) {
                $companions = [];
                foreach ($request->companions as $index => $companion) {
                    $companion['id_image'] = $request->file("companions.{$index}.id_image");
                    $companion['marriage_doc'] = $request->file("companions.{$index}.marriage_doc");
                    $companions[] = $companion;
                }
                $data['companions'] = $companions;
            }

            $reservation = $this->checkInService->createCheckIn($data, auth()->user());

            return redirect()->route('reservations.show', $reservation->id)
                ->with('success', 'تم تسجيل الدخول بنجاح');
        } catch (BlacklistedException $e) {
            return back()->withErrors(['id_number' => $e->getMessage()])->withInput();
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function success(Reservation $reservation)
    {
        $reservation->load(['guest', 'room.roomType', 'companions']);
        return view('checkin.success', compact('reservation'));
    }

    public function exportGovernment(Request $request, Reservation $reservation)
    {
        $format = $request->input('format', 'pdf');

        if ($format === 'excel') {
            $filePath = $this->exportService->exportGuestExcel($reservation);
        } else {
            $filePath = $this->exportService->exportGuestPDF($reservation);
        }

        return Storage::disk('private')->download($filePath);
    }

    public function guestSearch(Request $request)
    {
        $q = trim($request->input('q', ''));
        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $guests = Guest::where('full_name', 'like', "%{$q}%")
            ->orderBy('full_name')
            ->limit(8)
            ->get(['id', 'full_name', 'nationality', 'occupation', 'id_type', 'id_number',
                   'id_issuer', 'id_issue_date', 'phone']);

        return response()->json($guests->map(fn($g) => [
            'id'            => $g->id,
            'full_name'     => $g->full_name,
            'nationality'   => $g->nationality ?? '',
            'occupation'    => $g->occupation ?? '',
            'id_type'       => $g->id_type,
            'id_number'     => $g->id_number,
            'id_issuer'     => $g->id_issuer ?? '',
            'id_issue_date' => $g->id_issue_date?->format('Y-m-d') ?? '',
            'phone'         => $g->phone ?? '',
        ]));
    }

    public function guestLookup(Request $request)
    {
        $idNumber = $request->input('id_number');
        if (empty($idNumber) || strlen($idNumber) < 3) {
            return response()->json(['found' => false]);
        }

        $guest = Guest::searchByIdNumber($idNumber)->withCount('reservations')->first();

        if (!$guest) {
            return response()->json(['found' => false]);
        }

        $lastReservation = $guest->reservations()->latest('check_in_date')->first();

        return response()->json([
            'found'              => true,
            'full_name'          => $guest->full_name,
            'nationality'        => $guest->nationality,
            'occupation'         => $guest->occupation ?? '',
            'id_type'            => $guest->id_type,
            'id_issuer'          => $guest->id_issuer ?? '',
            'id_issue_date'      => $guest->id_issue_date?->format('Y-m-d') ?? '',
            'phone'              => $guest->phone ?? '',
            'reservations_count' => $guest->reservations_count,
            'last_visit'         => $lastReservation?->check_in_date?->format('d/m/Y'),
        ]);
    }

    public function serveGuestIdImage(\App\Models\Guest $guest)
    {
        if (!$guest->id_image_path || !StorageHelper::exists($guest->id_image_path)) {
            Log::warning('Guest ID image not found', [
                'guest_id' => $guest->id,
                'path'     => $guest->id_image_path,
                'disk'     => StorageHelper::privateDisk(),
                'abs_path' => $guest->id_image_path ? StorageHelper::path($guest->id_image_path) : null,
            ]);
            abort(404);
        }
        return StorageHelper::response($guest->id_image_path);
    }

    public function serveCompanionIdImage(\App\Models\Companion $companion)
    {
        if (!$companion->id_image_path || !StorageHelper::exists($companion->id_image_path)) {
            Log::warning('Companion ID image not found', [
                'companion_id' => $companion->id,
                'path'         => $companion->id_image_path,
                'disk'         => StorageHelper::privateDisk(),
                'abs_path'     => $companion->id_image_path ? StorageHelper::path($companion->id_image_path) : null,
            ]);
            abort(404);
        }
        return StorageHelper::response($companion->id_image_path);
    }

    public function serveMarriageDoc(\App\Models\Companion $companion)
    {
        if (!$companion->marriage_doc_path || !StorageHelper::exists($companion->marriage_doc_path)) {
            Log::warning('Companion marriage doc not found', [
                'companion_id' => $companion->id,
                'path'         => $companion->marriage_doc_path,
                'disk'         => StorageHelper::privateDisk(),
                'abs_path'     => $companion->marriage_doc_path ? StorageHelper::path($companion->marriage_doc_path) : null,
            ]);
            abort(404);
        }
        return StorageHelper::response($companion->marriage_doc_path);
    }

    private function getNationalities(): array
    {
        return [
            'يمني', 'سعودي', 'مصري', 'سوري', 'عراقي', 'أردني', 'لبناني',
            'سوداني', 'صومالي', 'إثيوبي', 'هندي', 'باكستاني', 'أمريكي',
            'بريطاني', 'فرنسي', 'ألماني', 'أخرى',
        ];
    }
}
