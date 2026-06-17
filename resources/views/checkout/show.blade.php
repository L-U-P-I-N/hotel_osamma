@extends('layouts.app')
@section('title', 'تسجيل الخروج')
@section('page-title', 'تسجيل الخروج')

@section('content')
<div x-data="checkoutForm()" class="max-w-3xl mx-auto space-y-5">

<!-- Guest Summary -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    <h3 class="font-semibold text-gray-700 mb-4">ملخص الحجز</h3>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-gray-50 rounded-lg p-3 text-center">
            <div class="text-xs text-gray-500 mb-1">النزيل</div>
            <div class="font-semibold text-gray-800 text-sm">{{ $reservation->guest?->full_name ?? '—' }}</div>
        </div>
        <div class="bg-gray-50 rounded-lg p-3 text-center">
            <div class="text-xs text-gray-500 mb-1">الغرفة</div>
            <div class="font-bold text-primary-800 text-lg">{{ $reservation->room?->room_number ?? '—' }}</div>
        </div>
        <div class="bg-gray-50 rounded-lg p-3 text-center">
            <div class="text-xs text-gray-500 mb-1">تاريخ الدخول</div>
            <div class="font-medium text-gray-700 text-sm">{{ $reservation->check_in_date?->format('d/m/Y') ?? '—' }}</div>
        </div>
        <div class="bg-gray-50 rounded-lg p-3 text-center">
            <div class="text-xs text-gray-500 mb-1">تاريخ الخروج</div>
            <div class="font-medium text-gray-700 text-sm">{{ $reservation->check_out_date?->format('d/m/Y') ?? '—' }}</div>
        </div>
    </div>

    <!-- Balance Summary -->
    <div class="mt-4 grid grid-cols-3 gap-3">
        <div class="bg-blue-50 rounded-lg p-3 text-center border border-blue-100">
            <div class="text-xs text-blue-500 mb-1">الإجمالي</div>
            <div class="font-bold text-blue-800">{{ number_format($reservation->total_amount, 2) }}</div>
            <div class="text-xs text-blue-400">{{ $reservation->currency_symbol }}</div>
        </div>
        <div class="bg-green-50 rounded-lg p-3 text-center border border-green-100">
            <div class="text-xs text-green-500 mb-1">المدفوع</div>
            <div class="font-bold text-green-800">{{ number_format($reservation->paid_amount, 2) }}</div>
            <div class="text-xs text-green-400">{{ $reservation->currency_symbol }}</div>
        </div>
        <div class="rounded-lg p-3 text-center border {{ $reservation->balance > 0 ? 'bg-red-50 border-red-100' : 'bg-gray-50 border-gray-100' }}">
            <div class="text-xs {{ $reservation->balance > 0 ? 'text-red-500' : 'text-gray-500' }} mb-1">المتبقي</div>
            <div class="font-bold {{ $reservation->balance > 0 ? 'text-red-800' : 'text-gray-800' }}">{{ number_format($reservation->balance, 2) }}</div>
            <div class="text-xs {{ $reservation->balance > 0 ? 'text-red-400' : 'text-gray-400' }}">{{ $reservation->currency_symbol }}</div>
        </div>
    </div>
</div>

<form method="POST" action="{{ route('checkout.process', $reservation) }}" enctype="multipart/form-data" class="space-y-5">
    @csrf
    @method('POST')

    <!-- Room Inspection -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <h3 class="font-semibold text-gray-700 mb-4">فحص الغرفة</h3>

        <div class="flex items-center gap-3 mb-4">
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" name="has_damage" value="1" x-model="hasDamage" class="sr-only peer">
                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:bg-red-500 after:content-[''] after:absolute after:top-[2px] after:right-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all rtl:peer-checked:after:-translate-x-full"></div>
            </label>
            <span class="text-sm font-medium" :class="hasDamage ? 'text-red-700' : 'text-gray-700'">
                <span x-text="hasDamage ? 'يوجد أضرار في الغرفة' : 'لا توجد أضرار'"></span>
            </span>
        </div>

        <div x-show="hasDamage" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">وصف الأضرار *</label>
                <textarea name="damage_description" rows="3" :required="hasDamage"
                          class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500 outline-none resize-none"
                          placeholder="صف الأضرار بالتفصيل..."></textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">صور الأضرار</label>
                <input type="file" name="inspection_images[]" accept="image/*" multiple
                       class="w-full text-sm text-gray-600 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:bg-red-50 file:text-red-700 hover:file:bg-red-100">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">مبلغ التعويض</label>
                <input type="number" name="compensation_amount" x-model="compensationAmount" step="0.01" min="0"
                       class="w-full md:w-64 border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
            </div>

            <div x-show="compensationAmount > 0" class="bg-orange-50 border border-orange-200 rounded-lg p-4 space-y-3">
                <h4 class="font-medium text-orange-800 text-sm">دفع التعويض</h4>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">طريقة الدفع</label>
                        <select name="compensation_method" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
                            <option value="cash">نقدي</option>
                            <option value="pos">POS</option>
                            <option value="bank_transfer">تحويل</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">المبلغ المدفوع</label>
                        <input type="number" name="compensation_paid" step="0.01" min="0" :max="compensationAmount"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Remaining Balance Payment -->
    @if($reservation->balance > 0)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <h3 class="font-semibold text-gray-700 mb-4">تسوية الرصيد المتبقي</h3>
        <div class="bg-red-50 border border-red-200 rounded-lg p-3 mb-4 text-sm text-red-800">
            المبلغ المتبقي: <strong>{{ number_format($reservation->balance, 2) }} ر.ي</strong>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">المبلغ</label>
                <input type="number" name="remaining_payment" step="0.01" min="0"
                       value="{{ $reservation->balance }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">طريقة الدفع</label>
                <select name="remaining_method" x-model="remainingMethod"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
                    <option value="cash">نقدي</option>
                    <option value="pos">POS</option>
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
            <div x-show="remainingMethod === 'bank_transfer'" class="md:col-span-3">
                <label class="block text-xs font-medium text-gray-600 mb-1">سند التحويل *</label>
                <input type="file" name="remaining_bank_receipt" accept="image/*,.pdf"
                       class="w-full text-sm text-gray-600 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
            </div>
        </div>
    </div>
    @endif

    <!-- Submit -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mb-4 text-sm text-yellow-800">
            <strong>تحذير:</strong> بعد إتمام تسجيل الخروج لا يمكن التراجع عنه.
        </div>
        <div class="flex gap-3">
            <button type="submit"
                    class="flex items-center gap-2 px-6 py-3 bg-red-600 text-white rounded-lg font-semibold hover:bg-red-700 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                إتمام تسجيل الخروج
            </button>
            <a href="{{ route('reservations.show', $reservation) }}" class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition text-sm">إلغاء</a>
        </div>
    </div>
</form>
</div>
@endsection

@push('scripts')
<script>
function checkoutForm() {
    return {
        hasDamage: false,
        compensationAmount: 0,
        remainingMethod: 'cash',
    }
}
</script>
@endpush
