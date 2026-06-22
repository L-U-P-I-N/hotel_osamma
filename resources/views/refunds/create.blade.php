@extends('layouts.app')
@section('title', 'استرجاع مبلغ')
@section('page-title', 'استرجاع مبلغ')

@section('content')
<div class="max-w-2xl">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-4">
        <h3 class="font-semibold text-gray-800 mb-3">بيانات الحجز</h3>
        <div class="grid grid-cols-2 gap-3 text-sm">
            <div><span class="text-gray-500">النزيل:</span> <strong>{{ $reservation->guest?->full_name }}</strong></div>
            <div><span class="text-gray-500">الغرفة:</span> <strong>{{ $reservation->room?->room_number }}</strong></div>
            <div><span class="text-gray-500">الإجمالي:</span> <strong>{{ number_format($reservation->total_amount, 0) }} ر.ي</strong></div>
            <div><span class="text-gray-500">المدفوع:</span> <strong class="text-green-600">{{ number_format($reservation->paid_amount, 0) }} ر.ي</strong></div>
        </div>
    </div>

    <div class="bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 mb-5 text-sm text-amber-800">
        الحد الأقصى للاسترجاع: <strong>{{ number_format($reservation->paid_amount, 0) }} ر.ي</strong>
    </div>

    <form action="{{ route('refunds.store', $reservation) }}" method="POST" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        @csrf
        @if($errors->any())
        <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
            <ul class="list-disc pr-4 space-y-1">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
        @endif

        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">مبلغ الاسترجاع <span class="text-red-500">*</span></label>
                <input type="number" name="amount" step="1" min="1" max="{{ $reservation->paid_amount }}"
                       value="{{ old('amount') }}"
                       class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm @error('amount') border-red-400 @enderror">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">الدفعة المرتبطة (اختياري)</label>
                <select name="payment_id" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                    <option value="">— اختر دفعة —</option>
                    @foreach($reservation->payments as $p)
                    <option value="{{ $p->id }}">{{ $p->payment_date?->format('d/m/Y') }} — {{ number_format($p->amount,0) }} ر.ي ({{ match($p->method){ 'cash'=>'نقداً','pos'=>'POS','bank_transfer'=>'تحويل',default=>$p->method } }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">طريقة الاسترجاع <span class="text-red-500">*</span></label>
                <select name="method" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm @error('method') border-red-400 @enderror">
                    <option value="cash" {{ old('method')=='cash'?'selected':'' }}>نقداً</option>
                    <option value="bank_transfer" {{ old('method')=='bank_transfer'?'selected':'' }}>تحويل بنكي</option>
                    <option value="pos" {{ old('method')=='pos'?'selected':'' }}>POS</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">سبب الاسترجاع <span class="text-red-500">*</span></label>
                <textarea name="reason" rows="3"
                          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm @error('reason') border-red-400 @enderror"
                          placeholder="اذكر سبب الاسترجاع بوضوح...">{{ old('reason') }}</textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">ملاحظات إضافية</label>
                <input type="text" name="notes" value="{{ old('notes') }}"
                       class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
            </div>
        </div>

        <div class="flex gap-3 mt-6">
            <button type="submit"
                    class="px-6 py-2 bg-rose-600 text-white rounded-lg text-sm font-medium hover:bg-rose-700">
                تأكيد الاسترجاع
            </button>
            <a href="{{ route('reservations.show', $reservation) }}"
               class="px-6 py-2 border border-gray-200 text-gray-600 rounded-lg text-sm hover:bg-gray-50">
                إلغاء
            </a>
        </div>
    </form>
</div>
@endsection
