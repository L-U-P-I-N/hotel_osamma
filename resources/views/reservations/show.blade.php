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
    <div class="flex items-center gap-3 mr-auto">
        @if($reservation->status === 'checked_in')
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
            <div class="flex justify-between py-1.5 border-b border-gray-50"><span class="text-gray-500">الاسم</span><span class="font-medium">{{ $reservation->guest->full_name }}</span></div>
            <div class="flex justify-between py-1.5 border-b border-gray-50"><span class="text-gray-500">الجنسية</span><span>{{ $reservation->guest->nationality ?: '-' }}</span></div>
            <div class="flex justify-between py-1.5 border-b border-gray-50"><span class="text-gray-500">نوع الهوية</span><span>{{ $reservation->guest->getIdTypeLabel() }}</span></div>
            @can('guests.sensitive')
            <div class="flex justify-between py-1.5 border-b border-gray-50"><span class="text-gray-500">رقم الهوية</span><span class="font-mono">{{ $reservation->guest->id_number }}</span></div>
            <div class="flex justify-between py-1.5 border-b border-gray-50"><span class="text-gray-500">الجوال</span><span class="font-mono">{{ $reservation->guest->phone ?: '-' }}</span></div>
            @endcan
            <div class="flex justify-between py-1.5 border-b border-gray-50"><span class="text-gray-500">جهة القدوم</span><span>{{ $reservation->origin ?: '-' }}</span></div>
            <div class="flex justify-between py-1.5"><span class="text-gray-500">الغرض</span><span>{{ $reservation->purpose ?: '-' }}</span></div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <h3 class="font-semibold text-gray-700 mb-4">بيانات الحجز</h3>
        <div class="space-y-2 text-sm">
            <div class="flex justify-between py-1.5 border-b border-gray-50"><span class="text-gray-500">الغرفة</span><span class="font-bold text-primary-800">{{ $reservation->room->room_number }}</span></div>
            <div class="flex justify-between py-1.5 border-b border-gray-50"><span class="text-gray-500">النوع</span><span>{{ $reservation->room->roomType->name }}</span></div>
            <div class="flex justify-between py-1.5 border-b border-gray-50"><span class="text-gray-500">تاريخ الدخول</span><span>{{ $reservation->check_in_date->format('d/m/Y') }}</span></div>
            <div class="flex justify-between py-1.5 border-b border-gray-50"><span class="text-gray-500">تاريخ الخروج</span><span>{{ $reservation->check_out_date->format('d/m/Y') }}</span></div>
            <div class="flex justify-between py-1.5 border-b border-gray-50"><span class="text-gray-500">عدد الليالي</span><span class="font-medium">{{ $reservation->nights }}</span></div>
            <div class="flex justify-between py-1.5 border-b border-gray-50"><span class="text-gray-500">الإجمالي</span><span class="font-bold">{{ number_format($reservation->total_amount, 2) }} ر.ي</span></div>
            <div class="flex justify-between py-1.5 border-b border-gray-50"><span class="text-gray-500">المدفوع</span><span class="font-medium text-green-600">{{ number_format($reservation->paid_amount, 2) }} ر.ي</span></div>
            <div class="flex justify-between py-1.5"><span class="text-gray-500">المتبقي</span><span class="font-bold {{ $reservation->balance > 0 ? 'text-red-600' : 'text-green-600' }}">{{ number_format($reservation->balance, 2) }} ر.ي</span></div>
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
                    <td class="px-4 py-2 text-gray-600">{{ $p->receivedBy->name }}</td>
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
@endsection
