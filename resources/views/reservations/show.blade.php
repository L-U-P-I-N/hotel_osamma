@extends('layouts.app')
@section('title', 'تفاصيل الحجز #' . $reservation->id)
@section('page-title', 'تفاصيل الحجز #' . $reservation->id)

@section('content')
<div class="max-w-4xl mx-auto space-y-5">

<!-- Status Bar -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex items-center justify-between flex-wrap gap-3">
    @php
        $sc = ['confirmed'=>'bg-blue-100 text-blue-800','checked_in'=>'bg-green-100 text-green-800','checked_out'=>'bg-gray-100 text-gray-800','cancelled'=>'bg-red-100 text-red-800'];
        $pc = ['unpaid'=>'bg-red-100 text-red-800','partial'=>'bg-yellow-100 text-yellow-800','paid'=>'bg-green-100 text-green-800','deferred'=>'bg-purple-100 text-purple-800'];
    @endphp
    <div class="flex items-center gap-3">
        <span class="text-gray-500 text-sm">حالة الحجز:</span>
        <span class="px-3 py-1 rounded-full text-sm font-medium {{ $sc[$reservation->status] ?? '' }}">{{ $reservation->status_label }}</span>
    </div>
    <div class="flex items-center gap-3">
        <span class="text-gray-500 text-sm">حالة الدفع:</span>
        <span class="px-3 py-1 rounded-full text-sm font-medium {{ $pc[$reservation->payment_status] ?? '' }}">{{ $reservation->payment_status_label }}</span>
    </div>
    <div class="flex items-center gap-2 mr-auto flex-wrap">
        @can('checkin.view')
        @if(in_array($reservation->status, ['confirmed','checked_in']))
        <a href="{{ route('reservations.edit', $reservation) }}" class="px-4 py-2 border text-sm rounded-lg hover:bg-gray-50 transition" style="border-color:#0F4C75;color:#0F4C75;">تعديل</a>
        @endif
        @if(!in_array($reservation->status, ['checked_out','cancelled']))
        <form method="POST" action="{{ route('reservations.cancel', $reservation) }}" onsubmit="return confirm('هل أنت متأكد من إلغاء هذا الحجز؟')">
            @csrf @method('PATCH')
            <button type="submit" class="px-4 py-2 border border-red-300 text-red-600 text-sm rounded-lg hover:bg-red-50 transition">إلغاء الحجز</button>
        </form>
        @endif
        @endcan
        @if($reservation->status === 'checked_in')
        @can('checkin.view')
        <button onclick="document.getElementById('transferRoomModal').classList.remove('hidden')"
                class="px-4 py-2 bg-orange-500 text-white rounded-lg text-sm hover:bg-orange-600 transition">تغيير الغرفة</button>
        <button onclick="document.getElementById('renewModal').classList.remove('hidden')"
                class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 transition">تجديد الإقامة</button>
        @endcan
        @can('checkout.process')
        <a href="{{ route('checkout.show', $reservation) }}" class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700 transition">تسجيل الخروج</a>
        @endcan
        @can('payments.create')
        @if($reservation->balance > 0)
        <button onclick="document.getElementById('paymentForm').classList.toggle('hidden')"
                class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700 transition">إضافة دفعة</button>
        @endif
        @endcan
        @endif
        @can('government.export')
        <a href="{{ route('checkin.exportGov', ['reservation'=>$reservation->id,'format'=>'pdf']) }}" class="px-4 py-2 bg-gray-800 text-white rounded-lg text-sm hover:bg-gray-700 transition">تصدير PDF</a>
        @endcan
    </div>
</div>

<!-- Guest + Reservation Info -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-5">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <h3 class="font-semibold text-gray-700 mb-4">بيانات النزيل</h3>
        <div class="space-y-2 text-sm">
            <div class="flex justify-between py-1.5 border-b border-gray-50"><span class="text-gray-500">الاسم</span><span class="font-medium">{{ $reservation->guest?->full_name ?? '—' }}</span></div>
            <div class="flex justify-between py-1.5 border-b border-gray-50"><span class="text-gray-500">الجنسية</span><span>{{ $reservation->guest?->nationality ?: '-' }}</span></div>
            <div class="flex justify-between py-1.5 border-b border-gray-50"><span class="text-gray-500">نوع الهوية</span><span>{{ $reservation->guest?->getIdTypeLabel() ?? '—' }}</span></div>
            @can('guests.sensitive')
            <div class="flex justify-between py-1.5 border-b border-gray-50"><span class="text-gray-500">رقم الهوية</span><span class="font-mono">{{ $reservation->guest?->id_number ?? '—' }}</span></div>
            <div class="flex justify-between py-1.5 border-b border-gray-50"><span class="text-gray-500">الجوال</span><span class="font-mono">{{ $reservation->guest?->phone ?: '-' }}</span></div>
            @endcan
            <div class="flex justify-between py-1.5 border-b border-gray-50"><span class="text-gray-500">جهة القدوم</span><span>{{ $reservation->origin ?: '-' }}</span></div>
            <div class="flex justify-between py-1.5"><span class="text-gray-500">الغرض</span><span>{{ $reservation->purpose ?: '-' }}</span></div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <h3 class="font-semibold text-gray-700 mb-4">بيانات الحجز</h3>
        <div class="space-y-2 text-sm">
            <div class="flex justify-between py-1.5 border-b border-gray-50"><span class="text-gray-500">الغرفة</span><span class="font-bold text-primary-800">{{ $reservation->room?->room_number ?? '—' }}</span></div>
            <div class="flex justify-between py-1.5 border-b border-gray-50"><span class="text-gray-500">النوع</span><span>{{ $reservation->room?->roomType?->name ?? '—' }}</span></div>
            <div class="flex justify-between py-1.5 border-b border-gray-50"><span class="text-gray-500">تاريخ الدخول</span><span>{{ $reservation->check_in_date?->format('d/m/Y') ?? '—' }}</span></div>
            <div class="flex justify-between py-1.5 border-b border-gray-50"><span class="text-gray-500">تاريخ الخروج</span><span>{{ $reservation->check_out_date?->format('d/m/Y') ?? '—' }}</span></div>
            <div class="flex justify-between py-1.5 border-b border-gray-50"><span class="text-gray-500">عدد الليالي</span><span class="font-medium">{{ $reservation->nights }}</span></div>
            <div class="flex justify-between py-1.5 border-b border-gray-50"><span class="text-gray-500">الإجمالي</span><span class="font-bold">{{ number_format($reservation->total_amount, 2) }} {{ $reservation->currency_symbol }}</span></div>
            <div class="flex justify-between py-1.5 border-b border-gray-50"><span class="text-gray-500">المدفوع</span><span class="font-medium text-green-600">{{ number_format($reservation->paid_amount, 2) }} {{ $reservation->currency_symbol }}</span></div>
            <div class="flex justify-between py-1.5"><span class="text-gray-500">المتبقي</span><span class="font-bold {{ $reservation->balance > 0 ? 'text-red-600' : 'text-green-600' }}">{{ number_format($reservation->balance, 2) }} {{ $reservation->currency_symbol }}</span></div>
        </div>
    </div>
</div>

<!-- Payment Form (hidden by default) -->
@can('payments.create')
<div id="paymentForm" class="hidden bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    <h3 class="font-semibold text-gray-700 mb-4">إضافة دفعة</h3>
    <form method="POST" action="{{ route('payments.store') }}" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @csrf
        <input type="hidden" name="reservation_id" value="{{ $reservation->id }}">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">المبلغ *</label>
            <input type="number" name="amount" step="0.01" min="0.01" max="{{ $reservation->balance }}"
                   value="{{ $reservation->balance }}" required
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">طريقة الدفع</label>
            <select name="method" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
                <option value="cash">نقدي</option>
                <option value="pos">شبكة POS</option>
                <option value="bank_transfer">تحويل بنكي</option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">العملة</label>
            <select name="currency" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
                <option value="YER">ريال يمني</option>
                <option value="SAR">ريال سعودي</option>
                <option value="USD">دولار أمريكي</option>
            </select>
        </div>
        <div class="md:col-span-2">
            <label class="block text-xs font-medium text-gray-600 mb-1">سند التحويل (إن وجد)</label>
            <input type="file" name="bank_receipt" accept="image/*,.pdf"
                   class="w-full text-sm text-gray-600 file:mr-2 file:py-1 file:px-3 file:rounded file:border-0 file:text-xs file:bg-primary-50 file:text-primary-700">
        </div>
        <div class="flex items-end">
            <button type="submit" class="w-full px-4 py-2 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700 transition">تسجيل الدفعة</button>
        </div>
    </form>
</div>
@endcan

<!-- Companions -->
@if($reservation->companions->count() > 0)
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    <h3 class="font-semibold text-gray-700 mb-4">المرافقون ({{ $reservation->companions->count() }})</h3>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50"><tr>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">الاسم</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">الجنسية</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">الصلة</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">نوع الهوية</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($reservation->companions as $c)
                <tr>
                    <td class="px-4 py-2 font-medium">{{ $c->full_name }}</td>
                    <td class="px-4 py-2 text-gray-600">{{ $c->nationality ?: '-' }}</td>
                    <td class="px-4 py-2">{{ $c->getRelationshipLabel() }}</td>
                    <td class="px-4 py-2 text-gray-600">{{ $c->id_type }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<!-- Payments -->
@if($reservation->payments->count() > 0)
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    <h3 class="font-semibold text-gray-700 mb-4">المدفوعات</h3>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50"><tr>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">التاريخ</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">المبلغ</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">الطريقة</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">النوع</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">استلم بواسطة</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">السند</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($reservation->payments as $p)
                <tr>
                    <td class="px-4 py-2 text-gray-600">{{ $p->payment_date->format('d/m/Y H:i') }}</td>
                    <td class="px-4 py-2 font-bold text-green-700">{{ number_format($p->amount, 2) }} {{ $p->currency }}</td>
                    <td class="px-4 py-2 text-gray-600">{{ match($p->method) { 'cash'=>'نقدي', 'pos'=>'POS', 'bank_transfer'=>'تحويل', default=>$p->method } }}</td>
                    <td class="px-4 py-2 text-gray-600">{{ match($p->type) { 'reservation'=>'حجز', 'compensation'=>'تعويض', 'extra_service'=>'خدمة إضافية', default=>$p->type } }}</td>
                    <td class="px-4 py-2 text-gray-600">{{ $p->receivedBy?->name ?? '—' }}</td>
                    <td class="px-4 py-2">
                        @if($p->bank_receipt_path)
                        @can('payments.bank_receipt')
                        <a href="{{ route('payments.receipt', ['file' => $p->bank_receipt_path]) }}" target="_blank" class="text-primary-600 hover:underline text-xs">عرض السند</a>
                        @endcan
                        @else
                        <span class="text-gray-300 text-xs">-</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<!-- Extra Charges -->
@if($reservation->extraCharges->count() > 0)
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    <h3 class="font-semibold text-gray-700 mb-4">الرسوم الإضافية</h3>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50"><tr>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">التاريخ</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">النوع</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">الوصف</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">المبلغ</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($reservation->extraCharges as $charge)
                <tr>
                    <td class="px-4 py-2 text-gray-600">{{ $charge->charge_date->format('d/m/Y') }}</td>
                    <td class="px-4 py-2 text-gray-600">{{ $charge->type }}</td>
                    <td class="px-4 py-2 text-gray-600">{{ $charge->description ?: '-' }}</td>
                    <td class="px-4 py-2 font-medium text-red-600">{{ number_format($charge->amount, 2) }} ر.ي</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<div class="flex justify-start">
    <a href="{{ route('reservations.index') }}" class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
        ← العودة للقائمة
    </a>
</div>

</div>

<!-- Renewal Modal -->
@if($reservation->status === 'checked_in')
@can('checkin.view')
<div id="renewModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
     onclick="if(event.target===this) this.classList.add('hidden')">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg" onclick="event.stopPropagation()">
        <div class="p-6 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-bold text-gray-800 text-lg">تجديد الإقامة</h3>
            <button onclick="document.getElementById('renewModal').classList.add('hidden')"
                    class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="{{ route('reservations.renew', $reservation) }}"
              x-data="renewForm()" class="p-6 space-y-4">
            @csrf
            <div class="grid grid-cols-2 gap-3 bg-gray-50 rounded-xl p-3 text-sm">
                <div><span class="text-gray-500">تاريخ الدخول:</span> <strong>{{ $reservation->check_in_date?->format('d/m/Y') ?? '—' }}</strong></div>
                <div><span class="text-gray-500">تاريخ الخروج الحالي:</span> <strong>{{ $reservation->check_out_date?->format('d/m/Y') ?? '—' }}</strong></div>
                <div><span class="text-gray-500">سعر الليلة:</span> <strong>{{ number_format($reservation->room?->roomType?->base_price ?? 0, 0) }} ر.ي</strong></div>
                <div><span class="text-gray-500">الرصيد المتبقي:</span> <strong class="{{ $reservation->balance > 0 ? 'text-red-600' : 'text-green-600' }}">{{ number_format($reservation->balance, 0) }} ر.ي</strong></div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">تاريخ الخروج الجديد <span class="text-red-500">*</span></label>
                <input type="date" name="new_check_out_date" x-model="newDate"
                       min="{{ $reservation->check_out_date?->addDay()->format('Y-m-d') ?? '' }}"
                       @change="calcExtra()" required
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
            </div>

            <div x-show="extraNights > 0" class="grid grid-cols-3 gap-3">
                <div class="bg-blue-50 rounded-xl p-3 text-center">
                    <div class="text-xl font-bold text-blue-700" x-text="extraNights"></div>
                    <div class="text-xs text-blue-500 mt-0.5">ليالٍ إضافية</div>
                </div>
                <div class="bg-green-50 rounded-xl p-3 text-center">
                    <div class="text-xl font-bold text-green-700" x-text="formatNum(extraAmount)"></div>
                    <div class="text-xs text-green-500 mt-0.5">مبلغ إضافي (ر.ي)</div>
                </div>
                <div class="bg-primary-50 rounded-xl p-3 text-center">
                    <div class="text-xl font-bold text-primary-700" x-text="formatNum({{ $reservation->total_amount }} + extraAmount)"></div>
                    <div class="text-xs text-primary-500 mt-0.5">الإجمالي الجديد</div>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">دفعة مقدمة (اختياري)</label>
                <div class="grid grid-cols-2 gap-3">
                    <input type="number" name="advance_payment" x-model.number="advancePay"
                           min="0" step="0.01" placeholder="0"
                           class="border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
                    <select name="payment_method" class="border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
                        <option value="cash">نقدي</option>
                        <option value="pos">شبكة POS</option>
                        <option value="bank_transfer">تحويل بنكي</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">ملاحظات</label>
                <input type="text" name="notes" placeholder="سبب التجديد..."
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" :disabled="extraNights <= 0"
                        class="flex-1 py-2.5 bg-blue-600 text-white rounded-lg font-semibold text-sm hover:bg-blue-700 transition disabled:opacity-40">
                    تأكيد التجديد
                </button>
                <button type="button" onclick="document.getElementById('renewModal').classList.add('hidden')"
                        class="px-6 py-2.5 border border-gray-300 text-gray-700 rounded-lg text-sm hover:bg-gray-50 transition">
                    إلغاء
                </button>
            </div>
        </form>
    </div>
</div>
<!-- Transfer Room Modal -->
@if($reservation->status === 'checked_in')
@can('checkin.view')
<div id="transferRoomModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
     onclick="if(event.target===this) this.classList.add('hidden')">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <div class="flex items-center justify-between p-5 border-b border-gray-100">
            <h3 class="text-lg font-semibold text-gray-800">تغيير الغرفة</h3>
            <button onclick="document.getElementById('transferRoomModal').classList.add('hidden')"
                    class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
        </div>
        <form method="POST" action="{{ route('reservations.transferRoom', $reservation) }}">
            @csrf
            <div class="p-5 space-y-4">
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 text-sm text-amber-800">
                    <p class="font-medium">الغرفة الحالية: <span class="font-bold">{{ $reservation->room?->room_number }}</span></p>
                    <p class="text-xs mt-0.5">ستنتقل الغرفة الحالية إلى وضع <strong>تحت الفحص</strong> بعد النقل</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">الغرفة الجديدة <span class="text-red-500">*</span></label>
                    <select name="new_room_id" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
                        <option value="">اختر غرفة متاحة...</option>
                        @foreach($availableRooms as $room)
                        <option value="{{ $room->id }}">
                            غرفة {{ $room->room_number }} — الطابق {{ $room->floor }}
                            @if($room->roomType) ({{ $room->roomType->name }}) @endif
                        </option>
                        @endforeach
                    </select>
                    @if($availableRooms->isEmpty())
                    <p class="text-xs text-red-500 mt-1">لا توجد غرف متاحة حالياً</p>
                    @endif
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">سبب التغيير</label>
                    <textarea name="notes" rows="2" placeholder="مثال: مشكلة في تكييف الغرفة..."
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 outline-none resize-none"></textarea>
                </div>
            </div>
            <div class="flex gap-3 px-5 pb-5">
                <button type="submit" {{ $availableRooms->isEmpty() ? 'disabled' : '' }}
                        class="flex-1 py-2.5 bg-orange-500 text-white rounded-lg text-sm font-medium hover:bg-orange-600 transition disabled:opacity-50">
                    تأكيد النقل
                </button>
                <button type="button" onclick="document.getElementById('transferRoomModal').classList.add('hidden')"
                        class="flex-1 py-2.5 border border-gray-300 text-gray-700 rounded-lg text-sm hover:bg-gray-50 transition">
                    إلغاء
                </button>
            </div>
        </form>
    </div>
</div>
@endcan
@endif

@push('scripts')
<script>
function renewForm() {
    return {
        newDate: '',
        extraNights: 0,
        extraAmount: 0,
        advancePay: 0,
        pricePerNight: {{ $reservation->room?->roomType?->base_price ?? 0 }},
        currentOut: '{{ $reservation->check_out_date?->format('Y-m-d') ?? '' }}',
        calcExtra() {
            if (!this.newDate) return;
            const d1 = new Date(this.currentOut), d2 = new Date(this.newDate);
            this.extraNights = Math.max(0, Math.floor((d2 - d1) / 86400000));
            this.extraAmount = this.extraNights * this.pricePerNight;
        },
        formatNum(n) { return (parseFloat(n)||0).toLocaleString('ar-YE'); },
    }
}
</script>
@endpush
@endcan
@endif

@endsection
