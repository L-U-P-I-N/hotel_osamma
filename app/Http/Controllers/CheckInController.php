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
use App\Rules\WithinPriceBounds;
use App\Services\CheckInService;
use App\Services\GovernmentExportService;
use App\Services\ShiftService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CheckInController extends Controller
{
    public function __construct(
        private CheckInService $checkInService,
        private GovernmentExportService $exportService,
        private ShiftService $shiftService
    ) {}

    public function create(Request $request)
    {
        $mode = 'checkin';

        $availableRooms = Room::with('roomType', 'linkedRoom')
            ->available()
            ->orderBy('floor')
            ->orderBy('room_number')
            ->get();

        // خريطة الغرف المتاحة حسب رقمها — لاشتقاق زوج الجناح (301A↔301B)
        // حتى عند غياب linked_room_id، فيظهر خيار حجز الجناح كاملاً A+B دائماً.
        $byNumber = $availableRooms->keyBy('room_number');
        $partnerOf = function (Room $room) use ($byNumber) {
            if ($room->linkedRoom) {
                return $room->linkedRoom;
            }
            if (in_array($room->room_sub_type, ['suite_a', 'suite_b'], true)) {
                $base = rtrim($room->room_number, 'ABab');
                $pairNumber = $base . ($room->room_sub_type === 'suite_a' ? 'B' : 'A');
                return $byNumber->get($pairNumber);
            }
            return null;
        };

        // Build a map of linked-room availability for the JS wizard
        $linkedAvailability = [];
        $pairedBIds = [];
        foreach ($availableRooms as $room) {
            $partner = $partnerOf($room);
            if (!$partner) {
                continue;
            }
            $linkedAvailability[$room->id] = [
                'linked_id'        => $partner->id,
                'linked_available' => $partner->status === 'available',
                'linked_number'    => $partner->room_number,
                'sub_type'         => $room->room_sub_type,
                'is_always_linked' => (bool)$room->is_always_linked,
            ];
            if ($room->room_sub_type === 'suite_a' && $partner->room_sub_type === 'suite_b') {
                $pairedBIds[] = $partner->id;
            }
        }

        $admins = User::role('admin')->where('is_active', true)->get();
        $nationalities = $this->getNationalities();
        $hasActiveShift = (bool) $this->shiftService->getActiveShift(auth()->user());

        // حجوزات مستقبلية (لم تبدأ بعد) لكل غرفة معروضة — تُستخدم لتنبيه الموظف
        // عند اختيار غرفة محجوزة مسبقاً لنزيل آخر سيصل قريباً
        $upcomingByRoom = Reservation::with('guest')
            ->whereIn('room_id', $availableRooms->pluck('id'))
            ->where('status', 'checked_in')
            ->whereDate('check_in_date', '>', today())
            ->orderBy('check_in_date')
            ->get()
            ->groupBy('room_id')
            ->map(fn($group) => $group->first());

        // Filter display rooms: hide suite_b when its suite_a pair is shown
        $displayRooms = $availableRooms->filter(
            fn($r) => !($r->room_sub_type === 'suite_b' && in_array($r->id, $pairedBIds))
        );
        $floors = $displayRooms->pluck('floor')->unique()->sort()->values();

        return view('checkin.create', compact('availableRooms', 'displayRooms', 'floors', 'linkedAvailability', 'admins', 'nationalities', 'mode', 'upcomingByRoom', 'hasActiveShift'));
    }

    public function store(Request $request)
    {
        // يجب أن تكون للموظف وردية مفتوحة قبل تسجيل دخول نزيل، وإلا تُستلَم دفعة
        // الوصول (إن وُجدت) دون أن تظهر ضمن أي وردية فتضيع من التسوية اليومية.
        if (!$this->shiftService->getActiveShift(auth()->user())) {
            $msg = 'لا يمكن تسجيل دخول نزيل دون وردية مفتوحة — افتح وردية أولاً من صفحة الورديات';
            if ($request->expectsJson()) {
                return response()->json(['message' => $msg], 422);
            }
            return back()->withErrors(['error' => $msg])->withInput();
        }

        // تسجيل تشخيصي: نُثبت وصول الطلب فعلاً إلى المتحكّم (لو لم يظهر هذا السطر
        // في السجل بعد محاولة الموظف، فالطلب لم يصل أصلاً — يُرفَض قبلها في طبقة
        // أعلى: 419 رمز/جلسة، أو 413 حجم رفع أكبر من حد الخادم). نُسجّل حجم الطلب
        // وعدد الملفات المرفوعة وأسماء الحقول الواصلة دون أي بيانات حسّاسة.
        Log::info('CHECKIN_STORE_ENTER', [
            'user_id'          => auth()->id(),
            'content_length'   => $request->header('Content-Length'),
            'files'            => array_keys($request->allFiles()),
            'has_token'        => $request->filled('_token'),
            'has_room'         => $request->filled('room_id'),
            'has_id_number'    => $request->filled('id_number'),
            'payment_status'   => $request->input('payment_status'),
        ]);

        // صيغ الصور المقبولة — تشمل صيغ هواتف الجوال الحديثة (HEIC/WebP) حتى لا تُرفض
        $imgMimes = 'jpg,jpeg,png,webp,gif,bmp,heic,heif,pdf';

        // نزيل عائد: إن كانت هويته مسجّلة مسبقاً ولها صورة محفوظة فلا نُلزمه برفع صورة جديدة.
        // id_number مُشفَّر (غير قابل للمطابقة المباشرة بـ WHERE)، فنبحث عبر بصمته —
        // كان Guest::where('id_number', ...) يفشل دائماً فيُلزم كل نزيل عائد برفع صورة
        // جديدة حتى لو كانت محفوظة لديه مسبقاً.
        $existingGuest = Guest::searchByIdNumber($request->input('id_number'))->first();
        $idImageRule = ($existingGuest && $existingGuest->id_image_path)
            ? "nullable|file|mimes:{$imgMimes}|max:5120"
            : "required|file|mimes:{$imgMimes}|max:5120";

        // رقم الجوال اختياري — إن تُرك فارغاً نُسجّل "لا يوجد"
        if (!$request->filled('phone')) {
            $request->merge(['phone' => 'لا يوجد']);
        }

        // نطاق السعر يعتمد على الغرفة ونوع حجز الجناح، فنجهّز القاعدة قبل التحقق
        $priceBoundsRule = new WithinPriceBounds(
            Room::with('roomType')->find($request->input('room_id')),
            auth()->user(),
            $request->input('suite_booking_type')
        );

        $request->validate([
            'full_name'   => 'required|string|max:255',
            'occupation'  => 'nullable|string|max:255',
            'phone'       => 'nullable|string|max:30',
            'id_type'     => 'required|in:passport,national_id,residence',
            'id_number'   => 'required|string',
            'id_image'    => $idImageRule,
            'nationality' => 'nullable|string|max:100',
            'origin'      => 'nullable|string|max:255',
            'purpose'     => 'nullable|string|max:255',
            'room_id'         => 'required|exists:rooms,id',
            'check_in_date'   => 'required|date',
            'check_in_time'   => 'nullable|regex:/^\d{2}:\d{2}$/',
            'check_out_date'  => 'required|date|after_or_equal:check_in_date',
            'check_out_time'  => 'nullable|regex:/^\d{2}:\d{2}$/',
            'payment_status'  => 'required|in:unpaid,partial,paid,deferred',
            'price_per_night'         => ['nullable', 'numeric', 'min:0', $priceBoundsRule],
            'first_night_price'       => ['nullable', 'numeric', 'min:0', $priceBoundsRule],
            'renewal_price_per_night' => ['nullable', 'numeric', 'min:0', $priceBoundsRule],
            'currency'        => 'nullable|in:YER',
            'bank_receipt' => "required_if:payment_method,bank_transfer|nullable|file|mimes:{$imgMimes}|max:10240",
            'companions.*.full_name' => 'required_with:companions.*.relationship|string',
            'companions.*.relationship' => 'nullable|in:wife,son,daughter,brother,sister,father,mother,other',
            'companions.*.nationality' => 'required_with:companions.*.full_name|nullable|string|max:100',
            'companions.*.id_image'    => "required_with:companions.*.full_name|nullable|file|mimes:{$imgMimes}|max:5120",
            'companions.*.marriage_doc' => "required_if:companions.*.relationship,wife|nullable|file|mimes:{$imgMimes}|max:5120",
            'suite_booking_type'        => 'nullable|in:a_only,b_only,both',
        ]);

        // وصلنا هنا = اجتاز الطلب التحقّق بنجاح (لو توقّف التنفيذ قبل هذا السطر
        // فالسبب فشل تحقّق يظهر للمستخدم في صندوق الأخطاء).
        Log::info('CHECKIN_STORE_VALIDATED', ['user_id' => auth()->id(), 'room_id' => $request->input('room_id')]);

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

            Log::info('CHECKIN_STORE_CREATED', ['user_id' => auth()->id(), 'reservation_id' => $reservation->id]);

            $successUrl = route('checkin.success', $reservation->id);

            // الإرسال عبر AJAX (الوضع الأساسي الآن): نُعيد رابط النجاح كـ JSON
            // فيوجّه المتصفح إليه دون إعادة تحميل الصفحة عند الفشل — فلا تُفقَد
            // بيانات الموظف أبداً مهما حدث. الطلب التقليدي (احتياطي) يُوجَّه مباشرةً.
            if ($request->expectsJson()) {
                return response()->json(['success' => true, 'redirect' => $successUrl]);
            }
            return redirect($successUrl)->with('success', 'تم تسجيل الدخول بنجاح');
        } catch (BlacklistedException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage(), 'errors' => ['id_number' => [$e->getMessage()]]], 422);
            }
            return back()->withErrors(['id_number' => $e->getMessage()])->withInput();
        } catch (\Throwable $e) {
            // نلتقط Throwable (يشمل Error/TypeError) حتى لا تظهر صفحة 500 صمّاء،
            // ونُسجّل التفاصيل الكاملة للتشخيص.
            Log::error('فشل تسجيل الدخول (check-in)', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile() . ':' . $e->getLine(),
                'user_id' => auth()->id(),
            ]);
            $msg = 'تعذّر إتمام تسجيل الدخول: ' . $e->getMessage();
            if ($request->expectsJson()) {
                return response()->json(['message' => $msg], 500);
            }
            return back()->withErrors(['error' => $msg])->withInput();
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
                   'id_issuer', 'id_issue_date', 'phone', 'id_image_path']);

        return response()->json($guests->map(function ($g) {
            // مرافقو آخر حجز لهذا النزيل العائد — تُعبَّأ بياناتهم النصية تلقائياً
            // (لا صور الهوية؛ تبقى مطلوبة عند كل تسجيل دخول جديد).
            $lastReservation = $g->reservations()->latest('check_in_date')->first();
            $companions = $lastReservation
                ? $lastReservation->companions()->get()->map(fn($c) => [
                    'full_name'     => $c->full_name,
                    'nationality'   => $c->nationality ?? '',
                    'id_type'       => $c->id_type ?? 'national_id',
                    'id_number'     => $c->id_number ?? '',
                    'id_issuer'     => $c->id_issuer ?? '',
                    'id_issue_date' => $c->id_issue_date?->format('Y-m-d') ?? '',
                    'relationship'  => $c->relationship ?? 'other',
                ])
                : collect();

            return [
                'id'            => $g->id,
                'full_name'     => $g->full_name,
                'nationality'   => $g->nationality ?? '',
                'occupation'    => $g->occupation ?? '',
                'id_type'       => $g->id_type,
                'id_number'     => $g->id_number,
                'id_issuer'     => $g->id_issuer ?? '',
                'id_issue_date' => $g->id_issue_date?->format('Y-m-d') ?? '',
                'phone'         => $g->phone ?? '',
                'has_id_image'  => (bool) $g->id_image_path,
                'companions'    => $companions,
            ];
        }));
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
            'id'                 => $guest->id,
            'full_name'          => $guest->full_name,
            'nationality'        => $guest->nationality,
            'occupation'         => $guest->occupation ?? '',
            'id_type'            => $guest->id_type,
            'id_issuer'          => $guest->id_issuer ?? '',
            'id_issue_date'      => $guest->id_issue_date?->format('Y-m-d') ?? '',
            'phone'              => $guest->phone ?? '',
            'reservations_count' => $guest->reservations_count,
            'last_visit'         => $lastReservation?->check_in_date?->format('d/m/Y'),
            'has_id_image'       => (bool) $guest->id_image_path,
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
