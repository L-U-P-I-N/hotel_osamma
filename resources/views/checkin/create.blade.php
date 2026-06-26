@extends('layouts.app')
@section('title', $mode === 'checkin' ? 'تسجيل الدخول' : 'حجز مسبق')
@section('page-title', $mode === 'checkin' ? 'تسجيل الدخول' : 'حجز مسبق')

@push('styles')
<style>
:root {
    --navy:       #0F4C75;
    --navy-d:     #0a3554;
    --navy-l:     #1B6CA8;
    --navy-g:     #EBF4FB;
    --gold:       #D4A017;
    --gold-l:     #FEF9E7;
    --emerald:    #059669;
    --emerald-l:  #D1FAE5;
    --ruby:       #DC2626;
    --ruby-l:     #FEE2E2;
    --card-r:     1rem;
    --sh:         0 1px 3px rgba(0,0,0,.07), 0 4px 16px rgba(0,0,0,.05);
    --sh-lg:      0 4px 24px rgba(0,0,0,.10), 0 12px 40px rgba(0,0,0,.08);
}

[x-cloak] { display: none !important; }

/* ── Micro-reset ── */
.ci-form input, .ci-form select, .ci-form textarea {
    font-family: inherit;
    outline: none;
    transition: border-color .15s, box-shadow .15s;
}
.ci-form input:focus, .ci-form select:focus, .ci-form textarea:focus {
    border-color: var(--navy-l) !important;
    box-shadow: 0 0 0 3px rgba(27,108,168,.15);
}

/* ── Form controls ── */
.fi {
    width: 100%;
    border: 1.5px solid #D1D5DB;
    border-radius: .625rem;
    padding: .6rem 1rem;
    font-size: .875rem;
    color: #1f2937;
    background: #fff;
    transition: border-color .15s, box-shadow .15s;
}
.fi:focus { border-color: var(--navy-l); box-shadow: 0 0 0 3px rgba(27,108,168,.15); }
.fi::placeholder { color: #9CA3AF; }
.fl {
    display: block;
    font-size: .8125rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: .375rem;
}
.freq { color: var(--ruby); margin-right: .125rem; }

/* ── Section cards ── */
.sec-card {
    background: #fff;
    border-radius: var(--card-r);
    box-shadow: var(--sh);
    border: 1.5px solid #F0F4F8;
    overflow: hidden;
}
.sec-hdr {
    display: flex;
    align-items: center;
    gap: .875rem;
    padding: 1.125rem 1.5rem;
    border-bottom: 1.5px solid #F0F4F8;
    background: linear-gradient(135deg, var(--navy-g) 0%, #fff 100%);
}
.sec-num {
    width: 2.25rem; height: 2.25rem;
    border-radius: 50%;
    background: var(--navy);
    color: #fff;
    font-size: .9375rem;
    font-weight: 800;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(15,76,117,.3);
}
.sec-title { font-size: 1rem; font-weight: 700; color: var(--navy-d); }
.sec-body { padding: 1.5rem; }

/* ── Room tiles ── */
.rtile {
    border: 1.5px solid #E5E7EB;
    border-radius: .75rem;
    padding: .75rem;
    cursor: pointer;
    transition: all .18s ease;
    background: #fff;
    position: relative;
}
.rtile:hover { border-color: var(--navy-l); background: var(--navy-g); transform: translateY(-1px); box-shadow: 0 4px 12px rgba(15,76,117,.12); }
.rtile.active { border-color: var(--navy); background: linear-gradient(135deg, var(--navy-g), #fff); box-shadow: 0 0 0 3px rgba(15,76,117,.15); }
.rtile-suite { border-color: #BFDBFE; background: #EFF6FF; }
.rtile-suite:hover { border-color: #3B82F6; background: #DBEAFE; }
.rtile-suite.active { border-color: #2563EB; background: linear-gradient(135deg,#DBEAFE,#EFF6FF); }

/* ── Filter pills ── */
.fpill {
    padding: .3125rem .875rem;
    border-radius: 9999px;
    font-size: .75rem;
    font-weight: 600;
    cursor: pointer;
    border: 1.5px solid transparent;
    transition: all .15s;
}
.fpill-off { background: #F3F4F6; color: #6B7280; border-color: #E5E7EB; }
.fpill-off:hover { background: var(--navy-g); border-color: var(--navy-l); color: var(--navy); }
.fpill-on { background: var(--navy); color: #fff; border-color: var(--navy); box-shadow: 0 2px 6px rgba(15,76,117,.25); }

/* ── Room type badges ── */
.rtag { display: inline-flex; align-items: center; padding: .125rem .5rem; border-radius: .375rem; font-size: .6875rem; font-weight: 700; }
.rtag-suite  { background: #DBEAFE; color: #1D4ED8; }
.rtag-double { background: #FCE7F3; color: #BE185D; }
.rtag-hall   { background: #F3F4F6; color: #4B5563; }
.rtag-apt    { background: #FEF3C7; color: #92400E; }

/* ── Suite A/B buttons ── */
.sbtna { border-radius: .5rem; padding: .4rem; font-size: .75rem; font-weight: 800; border: 1.5px solid; cursor: pointer; transition: all .15s; flex: 1; text-align: center; }

/* ── Payment option cards ── */
.pay-opt { position: relative; cursor: pointer; }
.pay-opt input[type="radio"] { position: absolute; opacity: 0; width: 0; height: 0; }
.pay-opt-inner {
    border: 1.5px solid #E5E7EB;
    border-radius: .75rem;
    padding: .875rem;
    text-align: center;
    transition: all .18s;
}
.pay-opt input:checked + .pay-opt-inner { box-shadow: 0 0 0 2px currentColor; }
.pay-opt-paid input:checked + .pay-opt-inner { border-color: var(--emerald); background: var(--emerald-l); color: var(--emerald); }
.pay-opt-partial input:checked + .pay-opt-inner { border-color: var(--navy-l); background: var(--navy-g); color: var(--navy); }


/* ── Booking panel ── */
.bpanel {
    background: #fff;
    border-radius: var(--card-r);
    box-shadow: var(--sh-lg);
    border: 1.5px solid #E5E7EB;
    overflow: hidden;
}
.bpanel-hdr {
    background: linear-gradient(135deg, var(--navy-d) 0%, var(--navy) 50%, var(--navy-l) 100%);
    padding: 1.25rem 1.5rem;
    color: #fff;
}
.bpanel-body { padding: 1.25rem; }

/* ── Stat boxes ── */
.stat-box {
    border-radius: .75rem;
    padding: .875rem .5rem;
    text-align: center;
    flex: 1;
}

/* ── Room selected banner ── */
.room-sel-banner {
    background: linear-gradient(135deg, var(--navy-g), #f0fdf4);
    border: 1.5px solid #B9D8F0;
    border-radius: .75rem;
    padding: .875rem 1rem;
}

/* ── Submit button ── */
.btn-submit {
    width: 100%;
    padding: .9375rem;
    border-radius: .75rem;
    font-size: 1rem;
    font-weight: 700;
    letter-spacing: .02em;
    cursor: pointer;
    border: none;
    transition: all .18s;
    background: linear-gradient(135deg, var(--emerald), #047857);
    color: #fff;
    box-shadow: 0 4px 12px rgba(5,150,105,.3);
}
.btn-submit:hover:not(:disabled) { background: linear-gradient(135deg, #047857, #065f46); box-shadow: 0 6px 20px rgba(5,150,105,.4); transform: translateY(-1px); }
.btn-submit:disabled { opacity: .6; cursor: not-allowed; transform: none; }

/* ── Mode banner ── */
.mode-banner { border-radius: .875rem; padding: .875rem 1.25rem; display: flex; align-items: center; gap: .75rem; font-size: .875rem; font-weight: 500; margin-bottom: 1.5rem; }
.mode-checkin { background: linear-gradient(135deg,#D1FAE5,#ECFDF5); border: 1.5px solid #6EE7B7; color: #065F46; }
.mode-reserve { background: linear-gradient(135deg,#DBEAFE,#EFF6FF); border: 1.5px solid #93C5FD; color: #1E40AF; }

/* ── Companion card ── */
.comp-card {
    border: 1.5px solid #E5E7EB;
    border-radius: .875rem;
    padding: 1.25rem;
    position: relative;
    background: #FAFAFA;
    transition: border-color .15s;
}
.comp-card:hover { border-color: #CBD5E1; }

/* ── Scrollbar styling for room grid ── */
.room-grid-scroll::-webkit-scrollbar { height: 6px; }
.room-grid-scroll::-webkit-scrollbar-track { background: #F3F4F6; border-radius: 3px; }
.room-grid-scroll::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 3px; }

/* ── Divider ── */
.sec-divider { height: 1.5px; background: linear-gradient(to right, transparent, #E5E7EB, transparent); margin: 1rem 0; }

/* ── Responsive ── */
@media (max-width: 1279px) {
    .bpanel { position: static !important; top: auto !important; }
}
</style>
@endpush

@section('content')
<div x-data="checkInForm()" x-init="init()" class="ci-form" x-cloak>

{{-- Mode Banner --}}
@if($mode === 'checkin')
<div class="mode-banner mode-checkin">
    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
    <span><strong>تسجيل دخول فوري</strong> — النزيل موجود الآن، ستصبح الغرفة <strong>مشغولة</strong> فور الحفظ</span>
</div>
@else
<div class="mode-banner mode-reserve">
    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
    <span><strong>حجز مسبق</strong> — سيتم تأكيد الحجز وتخصيص الغرفة للتاريخ المحدد</span>
</div>
@endif

{{-- Backend Validation Errors --}}
@if($errors->any())
<div class="mb-6 bg-red-50 border border-red-200 rounded-xl p-4">
    <div class="flex items-start gap-3">
        <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
        <div>
            <p class="font-semibold text-red-800 text-sm mb-1">يوجد أخطاء يرجى مراجعتها:</p>
            <ul class="text-red-700 text-sm space-y-0.5 list-disc list-inside">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
@endif

<form id="checkInMainForm" method="POST" action="{{ route('checkin.store') }}"
      enctype="multipart/form-data" autocomplete="off"
      @submit="submitting = true">
@csrf

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

{{-- ════════════════════════════════════════════════════════════
     LEFT COLUMN — Sections ① ② ③
     ════════════════════════════════════════════════════════════ --}}
<div class="xl:col-span-2 space-y-6">

    {{-- ─── ① بيانات النزيل ─── --}}
    <div class="sec-card">
        <div class="sec-hdr">
            <div class="sec-num">١</div>
            <div>
                <div class="sec-title">بيانات النزيل</div>
                <div class="text-xs text-gray-500 mt-0.5">المعلومات الشخصية وبيانات الهوية</div>
            </div>
            <svg class="w-6 h-6 text-navy-400 mr-auto opacity-40" style="color:var(--navy)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
        </div>
        <div class="sec-body">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                <div class="md:col-span-2">
                    <label class="fl">الاسم الرباعي <span class="freq">*</span></label>
                    <input type="text" name="full_name" x-model="guestData.full_name" required placeholder="أدخل الاسم الكامل رباعياً" class="fi">
                </div>

                <div>
                    <label class="fl">الجنسية</label>
                    <input type="text" name="nationality" x-model="guestData.nationality" placeholder="مثال: يمني، سعودي..." class="fi">
                </div>

                <div>
                    <label class="fl">المهنة</label>
                    <input type="text" name="occupation" x-model="guestData.occupation" placeholder="مثال: مهندس، طبيب..." class="fi">
                </div>

                <div>
                    <label class="fl">جهة القدوم</label>
                    <input type="text" name="origin" x-model="guestData.origin" placeholder="المدينة / المنطقة" class="fi">
                </div>

                <div>
                    <label class="fl">الغرض من القدوم</label>
                    <input type="text" name="purpose" x-model="guestData.purpose" placeholder="سياحة / عمل / علاج..." class="fi">
                </div>

                <div class="sec-divider md:col-span-2"></div>

                <div>
                    <label class="fl">نوع الهوية <span class="freq">*</span></label>
                    <select name="id_type" x-model="guestData.id_type" required class="fi">
                        <option value="national_id">هوية وطنية</option>
                        <option value="passport">جواز سفر</option>
                        <option value="residence">إقامة</option>
                    </select>
                </div>

                <div>
                    <label class="fl">رقم الهوية <span class="freq">*</span></label>
                    <input type="text" name="id_number" x-model="guestData.id_number" required placeholder="أدخل رقم الهوية" class="fi">
                </div>

                <div>
                    <label class="fl">جهة إصدار الهوية</label>
                    <input type="text" name="id_issuer" x-model="guestData.id_issuer" placeholder="الجهة المُصدِرة" class="fi">
                </div>

                <div>
                    <label class="fl">تاريخ الإصدار</label>
                    <input type="date" name="id_issue_date" x-model="guestData.id_issue_date" class="fi">
                </div>

                <div>
                    <label class="fl">رقم الجوال <span class="freq">*</span></label>
                    <input type="text" name="phone" x-model="guestData.phone" required placeholder="+967..." class="fi">
                </div>

                <div class="md:col-span-2">
                    <label class="fl">ملاحظات</label>
                    <textarea name="notes" x-model="guestData.notes" rows="2" placeholder="أي ملاحظات أو متطلبات خاصة..." class="fi resize-none"></textarea>
                </div>

                {{-- ID Image Upload --}}
                <div class="md:col-span-2">
                    <label class="fl">صورة الهوية <span class="freq">*</span></label>
                    <div class="border-2 border-dashed rounded-xl transition-all duration-200 cursor-pointer relative overflow-hidden group"
                         :class="idImagePreview ? 'border-emerald-300 bg-emerald-50' : 'border-gray-300 hover:border-navy-400 bg-gray-50 hover:bg-blue-50'"
                         style="--tw-border-opacity:1"
                         @dragover.prevent @drop.prevent="handleIdImageDrop($event)">
                        <input type="file" name="id_image" accept="image/*,.pdf" class="absolute inset-0 opacity-0 cursor-pointer z-10" @change="handleIdImageChange($event)">
                        <template x-if="!idImagePreview">
                            <div class="p-6 text-center">
                                <div class="w-14 h-14 rounded-xl bg-white shadow-sm flex items-center justify-center mx-auto mb-3">
                                    <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </div>
                                <p class="text-sm font-medium text-gray-600">اسحب الصورة هنا أو <span class="text-blue-600 font-semibold">انقر لاختيار ملف</span></p>
                                <p class="text-xs text-gray-400 mt-1">JPG · PNG · PDF — حتى 5MB</p>
                            </div>
                        </template>
                        <template x-if="idImagePreview && idImagePreview !== '__pdf__'">
                            <div class="p-4 flex items-center gap-4">
                                <img :src="idImagePreview" class="w-24 h-20 object-cover rounded-lg shadow-sm border border-gray-200">
                                <div>
                                    <div class="flex items-center gap-1.5 mb-1">
                                        <svg class="w-4 h-4 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                        <span class="text-sm font-semibold text-emerald-700">تم الرفع بنجاح</span>
                                    </div>
                                    <p class="text-xs text-gray-500" x-text="idImageName"></p>
                                    <p class="text-xs text-blue-600 mt-1 underline cursor-pointer">انقر لتغيير الصورة</p>
                                </div>
                            </div>
                        </template>
                        <template x-if="idImagePreview === '__pdf__'">
                            <div class="p-4 flex items-center gap-3">
                                <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center">
                                    <svg class="w-6 h-6 text-red-500" fill="currentColor" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6z"/><path d="M14 2v6h6M9 15h6M9 11h4"/></svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-emerald-700">تم اختيار ملف PDF ✓</p>
                                    <p class="text-xs text-gray-500 mt-0.5" x-text="idImageName"></p>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

            </div>
        </div>
    </div>{{-- /sec-card ① --}}

    {{-- ─── ② المرافقون ─── --}}
    <div class="sec-card">
        <div class="sec-hdr">
            <div class="sec-num">٢</div>
            <div>
                <div class="sec-title flex items-center gap-2">
                    المرافقون
                    <span x-show="companions.length > 0" class="px-2 py-0.5 text-xs font-bold rounded-full text-white" style="background:var(--navy)" x-text="companions.length"></span>
                </div>
                <div class="text-xs text-gray-500 mt-0.5">أفراد الأسرة المرافقون للنزيل (اختياري)</div>
            </div>
            <button type="button" @click="addCompanion()"
                    class="mr-auto flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-semibold text-white transition"
                    style="background:var(--navy)">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                إضافة
            </button>
        </div>
        <div class="sec-body">

            <div x-show="companions.length === 0" class="py-8 text-center">
                <svg class="w-14 h-14 mx-auto mb-3 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <p class="text-sm text-gray-400">لا يوجد مرافقون. انقر "إضافة" لإضافة أفراد العائلة.</p>
            </div>

            <div class="space-y-4">
                <template x-for="(comp, idx) in companions" :key="idx">
                    <div class="comp-card">
                        <button type="button" @click="removeCompanion(idx)"
                                class="absolute top-3 left-3 w-7 h-7 flex items-center justify-center rounded-full bg-red-50 text-red-400 hover:bg-red-100 hover:text-red-600 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                        <div class="flex items-center gap-2 mb-3 pb-2.5 border-b border-gray-200">
                            <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold text-white" style="background:var(--navy-l)" x-text="idx+1"></div>
                            <span class="text-sm font-semibold text-gray-700" x-text="`مرافق ${idx+1}`"></span>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div class="md:col-span-2">
                                <label class="fl text-xs">الاسم الكامل <span class="freq">*</span></label>
                                <input type="text" :name="`companions[${idx}][full_name]`" x-model="comp.full_name" required class="fi text-sm">
                            </div>
                            <div>
                                <label class="fl text-xs">صلة القرابة <span class="freq">*</span></label>
                                <select :name="`companions[${idx}][relationship]`" x-model="comp.relationship" required class="fi text-sm">
                                    <option value="wife">زوجة</option>
                                    <option value="son">ابن</option>
                                    <option value="daughter">ابنة</option>
                                    <option value="brother">أخ</option>
                                    <option value="sister">أخت</option>
                                    <option value="father">أب</option>
                                    <option value="mother">أم</option>
                                    <option value="other">أخرى</option>
                                </select>
                            </div>
                            <div>
                                <label class="fl text-xs">الجنسية <span class="freq">*</span></label>
                                <input type="text" :name="`companions[${idx}][nationality]`" x-model="comp.nationality" required class="fi text-sm">
                            </div>
                            <div>
                                <label class="fl text-xs">نوع الهوية</label>
                                <select :name="`companions[${idx}][id_type]`" x-model="comp.id_type" class="fi text-sm">
                                    <option value="national_id">هوية وطنية</option>
                                    <option value="passport">جواز سفر</option>
                                    <option value="residence">إقامة</option>
                                </select>
                            </div>
                            <div>
                                <label class="fl text-xs">رقم الهوية</label>
                                <input type="text" :name="`companions[${idx}][id_number]`" x-model="comp.id_number" class="fi text-sm">
                            </div>
                            <div>
                                <label class="fl text-xs">جهة الإصدار</label>
                                <input type="text" :name="`companions[${idx}][id_issuer]`" x-model="comp.id_issuer" class="fi text-sm">
                            </div>
                            <div>
                                <label class="fl text-xs">تاريخ الإصدار</label>
                                <input type="date" :name="`companions[${idx}][id_issue_date]`" x-model="comp.id_issue_date" class="fi text-sm">
                            </div>
                            <div class="md:col-span-2">
                                <label class="fl text-xs">صورة الهوية <span class="freq">*</span></label>
                                <input type="file" :name="`companions[${idx}][id_image]`" accept="image/*,.pdf" required
                                       @change="handleCompanionIdImage($event, idx)"
                                       class="fi text-sm text-gray-500 file:ml-2 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer">
                                <img x-show="comp.id_preview && comp.id_preview !== '__pdf__'" :src="comp.id_preview" class="mt-2 h-16 rounded-lg object-cover border border-gray-200">
                                <p x-show="comp.id_preview === '__pdf__'" class="mt-1 text-xs text-emerald-600 font-medium">تم اختيار ملف PDF ✓</p>
                            </div>
                            <div x-show="comp.relationship === 'wife'" class="md:col-span-3">
                                <label class="fl text-xs text-red-600">وثيقة الزواج (مطلوبة للزوجة) <span class="freq">*</span></label>
                                <input type="file" :name="`companions[${idx}][marriage_doc]`" accept="image/*,.pdf"
                                       :required="comp.relationship === 'wife'"
                                       class="fi text-sm text-gray-500 file:ml-2 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-red-50 file:text-red-700 hover:file:bg-red-100 cursor-pointer">
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>{{-- /sec-card ② --}}

    {{-- ─── ③ اختيار الغرفة ─── --}}
    <div class="sec-card">
        <div class="sec-hdr">
            <div class="sec-num">٣</div>
            <div>
                <div class="sec-title">اختيار الغرفة</div>
                <div class="text-xs text-gray-500 mt-0.5">اختر من الغرف المتاحة</div>
            </div>
            <svg class="w-6 h-6 mr-auto opacity-30" style="color:var(--navy)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
        </div>
        <div class="sec-body">

            <input type="hidden" name="room_id" x-model="roomId">
            <input type="hidden" name="suite_booking_type" x-model="suiteBookingType">

            {{-- Selected room banner --}}
            <div x-show="selectedRoom" x-transition class="room-sel-banner mb-4 flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white font-bold text-base flex-shrink-0 shadow-md"
                     style="background: linear-gradient(135deg, var(--navy), var(--navy-l))"
                     x-text="suiteBookingType === 'both' ? (selectedRoom?.room_number ?? '') + '+' + (linkedInfo?.linked_number ?? '') : (selectedRoom?.room_number ?? '')"></div>
                <div class="flex-1 min-w-0">
                    <div class="font-bold text-sm" style="color:var(--navy-d)" x-text="roomSelectionLabel()"></div>
                    <div class="text-xs text-gray-500 mt-0.5" x-text="(selectedRoom?.room_type_name ?? '') + ' — الطابق ' + (selectedRoom?.floor ?? '')"></div>
                    <div class="text-sm font-bold mt-0.5" style="color:var(--emerald)" x-text="formatNumber(effectiveRoomPrice()) + ' ر.ي / ليلة'"></div>
                </div>
                <button type="button" @click="clearRoomSelection()"
                        class="flex-shrink-0 text-xs px-3 py-1.5 rounded-lg border border-gray-300 text-gray-500 hover:border-red-300 hover:text-red-500 transition">
                    تغيير
                </button>
            </div>

            {{-- Apartment always-linked notice --}}
            <div x-show="selectedRoom && linkedInfo?.is_always_linked" class="mb-4 p-3 rounded-xl text-sm flex items-start gap-2" style="background:#FEF3C7; border:1.5px solid #FCD34D; color:#92400E">
                <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                <div><strong>الشقة تُحجز دائماً كوحدة كاملة</strong> — سيشمل الحجز الغرفتين <span x-text="(selectedRoom?.room_number ?? '') + ' و ' + (linkedInfo?.linked_number ?? '')"></span> تلقائياً.</div>
            </div>

            {{-- Filters --}}
            <div class="space-y-2.5 mb-5 p-3 rounded-xl bg-gray-50 border border-gray-100">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-xs font-bold text-gray-400 w-10 flex-shrink-0">الطابق</span>
                    <button type="button" @click="floorFilter = 'all'" :class="floorFilter === 'all' ? 'fpill fpill-on' : 'fpill fpill-off'" class="fpill">الكل</button>
                    @foreach($floors as $floor)
                    <button type="button" @click="floorFilter = '{{ $floor }}'" :class="floorFilter === '{{ $floor }}' ? 'fpill fpill-on' : 'fpill fpill-off'" class="fpill">ط{{ $floor }}</button>
                    @endforeach
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-xs font-bold text-gray-400 w-10 flex-shrink-0">النوع</span>
                    <button type="button" @click="typeFilter = 'all'"     :class="typeFilter === 'all'     ? 'fpill fpill-on' : 'fpill fpill-off'" class="fpill">الكل</button>
                    <button type="button" @click="typeFilter = 'regular'" :class="typeFilter === 'regular' ? 'fpill fpill-on' : 'fpill fpill-off'" class="fpill">عادية</button>
                    <button type="button" @click="typeFilter = 'double'"  :class="typeFilter === 'double'  ? 'fpill fpill-on' : 'fpill fpill-off'" class="fpill">زوجية</button>
                    <button type="button" @click="typeFilter = 'suite'"   :class="typeFilter === 'suite'   ? 'fpill fpill-on' : 'fpill fpill-off'" class="fpill">جناح</button>
                    <button type="button" @click="typeFilter = 'hall'"    :class="typeFilter === 'hall'    ? 'fpill fpill-on' : 'fpill fpill-off'" class="fpill">صالة</button>
                </div>
            </div>

            {{-- Room Grid --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">

                @foreach($displayRooms as $room)
                @php
                    $info = $linkedAvailability[$room->id] ?? null;
                    $roomData = [
                        'id'              => $room->id,
                        'room_number'     => $room->room_number,
                        'floor'           => $room->floor,
                        'base_price'      => (float)$room->roomType->base_price,
                        'prices'          => $room->pricesArray(),
                        'suite_price_yer' => (float)($room->suite_price_yer ?? 0),
                        'room_type_name'  => $room->roomType->name,
                        'sub_type'        => $room->room_sub_type,
                    ];
                    $roomTypeKey = match($room->room_sub_type) {
                        'suite_a', 'suite_b' => 'suite',
                        'hall'               => 'hall',
                        'double'             => 'double',
                        default              => 'regular',
                    };
                @endphp

                @if($room->room_sub_type === 'suite_a')
                @php
                    $doorLabel = rtrim($room->room_number, 'AB');
                    $linkedRoomData = $info ? [
                        'id'              => $info['linked_id'],
                        'room_number'     => $info['linked_number'],
                        'floor'           => $room->floor,
                        'base_price'      => (float)$room->roomType->base_price,
                        'prices'          => $room->pricesArray(),
                        'suite_price_yer' => (float)($room->suite_price_yer ?? 0),
                        'room_type_name'  => $room->roomType->name,
                        'sub_type'        => 'suite_b',
                    ] : null;
                @endphp
                {{-- Suite door tile with A / B / A+B selector --}}
                <div x-show="(floorFilter === 'all' || floorFilter === '{{ $room->floor }}') && (typeFilter === 'all' || typeFilter === 'suite')"
                     class="rtile rtile-suite"
                     :class="(roomId == '{{ $room->id }}' || roomId == '{{ $info ? $info['linked_id'] : '' }}') ? 'active' : ''">
                    <div class="flex items-center justify-between mb-1.5">
                        <div class="flex items-center gap-1.5">
                            <span class="text-lg font-black text-gray-800">{{ $doorLabel }}</span>
                            <span class="rtag rtag-suite">جناح</span>
                        </div>
                        <span class="text-xs font-medium text-gray-400">ط{{ $room->floor }}</span>
                    </div>
                    <div class="text-xs text-gray-500 mb-0.5">{{ $room->roomType->name }}</div>
                    <div class="text-xs font-bold mb-2.5" style="color:var(--emerald)">{{ number_format($room->roomType->base_price,0) }} ر.ي</div>
                    <div class="flex gap-1">
                        {{-- A only --}}
                        <button type="button"
                                @click="selectRoom({{ json_encode($roomData) }}, {{ json_encode($info) }}, 'a_only')"
                                :class="roomId == '{{ $room->id }}' && suiteBookingType === 'a_only'
                                    ? 'sbtna bg-blue-600 text-white border-blue-600 shadow-sm'
                                    : 'sbtna bg-white text-gray-600 border-gray-300 hover:border-blue-500 hover:text-blue-700'">A</button>
                        @if($info && $info['linked_available'] && $linkedRoomData)
                        {{-- B only --}}
                        <button type="button"
                                @click="selectRoom({{ json_encode($linkedRoomData) }}, null, 'b_only')"
                                :class="roomId == '{{ $info['linked_id'] }}'
                                    ? 'sbtna bg-purple-600 text-white border-purple-600 shadow-sm'
                                    : 'sbtna bg-white text-gray-600 border-gray-300 hover:border-purple-500 hover:text-purple-700'">B</button>
                        {{-- A+B --}}
                        <button type="button"
                                @click="selectRoom({{ json_encode($roomData) }}, {{ json_encode($info) }}, 'both')"
                                :class="roomId == '{{ $room->id }}' && suiteBookingType === 'both'
                                    ? 'sbtna bg-indigo-600 text-white border-indigo-600 shadow-sm'
                                    : 'sbtna bg-white text-gray-600 border-gray-300 hover:border-indigo-500 hover:text-indigo-700'">A+B</button>
                        @else
                        <div class="sbtna border-gray-100 text-gray-300 bg-gray-50 cursor-not-allowed" title="البوابة B محجوزة" style="cursor:not-allowed">B</div>
                        <div class="sbtna border-gray-100 text-gray-300 bg-gray-50 cursor-not-allowed" title="البوابة B محجوزة" style="cursor:not-allowed">A+B</div>
                        @endif
                    </div>
                </div>

                @else
                {{-- Regular / Double / Hall / Apartment tile --}}
                <div x-show="(floorFilter === 'all' || floorFilter === '{{ $room->floor }}') && (typeFilter === 'all' || typeFilter === '{{ $roomTypeKey }}')"
                     @click="selectRoom({{ json_encode($roomData) }}, {{ json_encode($info) }})"
                     class="rtile"
                     :class="roomId == '{{ $room->id }}' ? 'active' : ''">
                    <div class="flex items-start justify-between mb-0.5">
                        <div class="flex items-center gap-1 flex-wrap">
                            <span class="text-lg font-black text-gray-800">{{ $room->room_number }}</span>
                            @if($room->room_sub_type === 'double')
                                <span class="rtag rtag-double">زوجية</span>
                            @elseif($room->room_sub_type === 'apartment')
                                <span class="rtag rtag-apt">شقة</span>
                            @elseif($room->room_sub_type === 'hall')
                                <span class="rtag rtag-hall">صالة</span>
                            @endif
                        </div>
                        <span class="text-xs text-gray-400 font-medium">ط{{ $room->floor }}</span>
                    </div>
                    <div class="text-xs text-gray-500 mb-0.5">{{ $room->roomType->name }}</div>
                    <div class="text-xs font-bold mt-1" style="color:var(--emerald)">{{ number_format($room->roomType->base_price,0) }} ر.ي</div>
                    @if($info && $info['linked_available'])
                        <div class="text-xs text-blue-500 mt-0.5 font-medium">+ {{ $info['linked_number'] }} متاحة</div>
                    @endif
                    {{-- Active checkmark --}}
                    <div x-show="roomId == '{{ $room->id }}'" class="absolute top-2 left-2 w-5 h-5 rounded-full flex items-center justify-center" style="background:var(--navy)">
                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                    </div>
                </div>
                @endif

                @endforeach

                @if($displayRooms->isEmpty())
                <div class="col-span-full py-12 text-center text-gray-400">
                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    <p class="text-sm">لا توجد غرف متاحة حالياً</p>
                </div>
                @endif
            </div>
        </div>
    </div>{{-- /sec-card ③ --}}

</div>{{-- /left col --}}

{{-- ════════════════════════════════════════════════════════════
     RIGHT PANEL — Dates ④, Payment ⑤, Submit
     ════════════════════════════════════════════════════════════ --}}
<div class="xl:col-span-1">
<div class="bpanel sticky top-4">

    {{-- Panel header --}}
    <div class="bpanel-hdr">
        <div class="flex items-center gap-2 mb-1">
            <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            <span class="font-bold text-base tracking-wide">ملخص الحجز</span>
        </div>
        <div class="text-xs opacity-60 mt-0.5">أدخل البيانات ليتم احتساب التكلفة تلقائياً</div>

        {{-- Room mini-summary in header --}}
        <div x-show="selectedRoom" class="mt-3 pt-3 border-t border-white/20">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-xs font-bold bg-white/20" x-text="suiteBookingType === 'both' ? (selectedRoom?.room_number ?? '') + '+' : (selectedRoom?.room_number ?? '')"></div>
                <div>
                    <div class="text-sm font-semibold" x-text="roomSelectionLabel()"></div>
                    <div class="text-xs opacity-70" x-text="selectedRoom?.room_type_name ?? ''"></div>
                </div>
            </div>
        </div>
        <div x-show="!selectedRoom" class="mt-3 pt-3 border-t border-white/20 text-xs opacity-50 flex items-center gap-1.5">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            لم يتم اختيار غرفة بعد
        </div>
    </div>

    <div class="bpanel-body space-y-5">

        {{-- ④ التواريخ --}}
        <div>
            <div class="flex items-center gap-2 mb-3">
                <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-black text-white flex-shrink-0" style="background:var(--navy-l)">٤</div>
                <span class="text-sm font-bold" style="color:var(--navy-d)">التواريخ</span>
            </div>

            <div class="space-y-3">
                {{-- Check-in date --}}
                <div>
                    <label class="fl text-xs">تاريخ الوصول <span class="freq">*</span></label>
                    @if($mode === 'checkin')
                    <div class="fi flex items-center gap-2 bg-gray-50 text-gray-700 cursor-default">
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span x-text="checkInDate" class="flex-1"></span>
                        <span class="text-xs text-gray-400">(اليوم)</span>
                    </div>
                    <input type="hidden" name="check_in_date" :value="checkInDate">
                    @else
                    <input type="date" name="check_in_date" x-model="checkInDate" required :min="today()" @change="calcTotal()" class="fi">
                    @endif
                </div>

                {{-- Nights stepper + checkout --}}
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="fl text-xs">الليالي</label>
                        <div class="flex items-center border-1.5 rounded-xl overflow-hidden" style="border:1.5px solid #D1D5DB">
                            <button type="button" @click="nightsInput = Math.max(1, nightsInput-1); updateCheckoutFromNights()"
                                    class="w-9 h-9 flex items-center justify-center text-gray-500 hover:bg-gray-100 transition font-bold text-lg flex-shrink-0">−</button>
                            <input type="number" x-model.number="nightsInput" min="1" max="365"
                                   @input="updateCheckoutFromNights()"
                                   class="flex-1 text-center font-bold text-base border-none outline-none py-1.5 text-gray-800 bg-transparent">
                            <button type="button" @click="nightsInput = Math.min(365, nightsInput+1); updateCheckoutFromNights()"
                                    class="w-9 h-9 flex items-center justify-center text-gray-500 hover:bg-gray-100 transition font-bold text-lg flex-shrink-0">+</button>
                        </div>
                    </div>
                    <div>
                        <label class="fl text-xs">وقت الوصول</label>
                        <input type="time" name="check_in_time" x-model="checkInTime" class="fi">
                    </div>
                </div>

                <div>
                    <label class="fl text-xs">تاريخ المغادرة <span class="freq">*</span></label>
                    <input type="date" name="check_out_date" x-model="checkOutDate" required
                           :min="checkInDate || today()" @change="calcTotal()" class="fi">
                </div>
            </div>
        </div>

        {{-- Price stats (show when room selected and dates set) --}}
        <div x-show="nights > 0 && selectedRoom" x-transition class="space-y-3">
            <div class="h-px bg-gray-100"></div>
            <div class="flex gap-2">
                <div class="stat-box bg-blue-50 border border-blue-100">
                    <div class="text-xl font-black text-blue-700" x-text="nights"></div>
                    <div class="text-xs text-blue-400 font-semibold mt-0.5">ليلة</div>
                </div>
                <div class="stat-box bg-amber-50 border border-amber-100 relative">
                    <div class="text-lg font-black text-amber-700 leading-tight" x-text="formatNumber(effectiveRoomPrice())"></div>
                    <div class="text-xs text-amber-400 font-semibold mt-0.5">ر.ي/ليلة</div>
                    <div x-show="customPrice !== null && customPrice !== ''" class="absolute -top-1.5 -right-1.5 px-1.5 py-0.5 text-xs font-bold rounded-full bg-amber-500 text-white">معدَّل</div>
                </div>
                <div class="stat-box border-2 relative" style="background:linear-gradient(135deg,var(--navy-g),#fff);border-color:var(--navy-l)">
                    <div class="text-lg font-black leading-tight" style="color:var(--navy-d)" x-text="formatNumber(totalAmount)"></div>
                    <div class="text-xs font-semibold mt-0.5" style="color:var(--navy-l)">الإجمالي ر.ي</div>
                </div>
            </div>

            {{-- Price Override --}}
            <div>
                <button type="button" x-show="!showPriceOverride" @click="showPriceOverride = true"
                        class="w-full py-2 px-3 rounded-xl border border-amber-200 bg-amber-50 text-amber-700 text-xs font-semibold hover:bg-amber-100 transition flex items-center justify-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    تعديل السعر (للتفاوض)
                </button>
                <div x-show="showPriceOverride" x-transition class="rounded-xl p-3 space-y-2 border border-amber-200 bg-amber-50">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-amber-800">سعر التفاوض (ر.ي/ليلة)</span>
                        <button type="button" @click="showPriceOverride = false; customPrice = null; calcTotal()" class="text-xs text-gray-400 hover:text-red-500 underline">إلغاء</button>
                    </div>
                    <input type="number" min="3000" max="100000" step="100"
                           :placeholder="'الأصلي: ' + formatNumber(roomBasePriceFor('YER'))"
                           x-model.number="customPrice" @input="calcTotal()"
                           class="fi text-sm border-amber-300 bg-white focus:border-amber-500">
                    <p class="text-xs text-amber-600">النطاق المسموح: 3,000 إلى 100,000 ر.ي</p>
                    <p x-show="customPrice !== null && customPrice !== '' && (parseFloat(customPrice) < 3000 || parseFloat(customPrice) > 100000)"
                       class="text-xs text-red-600 font-semibold">⚠ السعر خارج النطاق المسموح</p>
                </div>
            </div>
        </div>

        <input type="hidden" name="currency" value="YER">
        <input type="hidden" name="total_amount" :value="totalAmount">
        <input type="hidden" name="price_per_night" :value="effectiveRoomPrice()">
        <input type="hidden" name="booking_mode" value="{{ $mode }}">

        <div class="h-px bg-gray-100"></div>

        {{-- ⑤ الدفع --}}
        <div>
            <div class="flex items-center gap-2 mb-3">
                <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-black text-white flex-shrink-0" style="background:var(--navy-l)">٥</div>
                <span class="text-sm font-bold" style="color:var(--navy-d)">الدفع</span>
            </div>

            {{-- Payment Status --}}
            <div class="grid grid-cols-2 gap-2 mb-4">
                <label class="pay-opt pay-opt-paid">
                    <input type="radio" name="payment_status" value="paid" x-model="paymentStatus">
                    <div class="pay-opt-inner">
                        <svg class="w-5 h-5 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <div class="text-xs font-bold">مدفوع كامل</div>
                    </div>
                </label>
                <label class="pay-opt pay-opt-partial">
                    <input type="radio" name="payment_status" value="partial" x-model="paymentStatus">
                    <div class="pay-opt-inner">
                        <svg class="w-5 h-5 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <div class="text-xs font-bold">دفعة جزئية</div>
                    </div>
                </label>
            </div>

            {{-- Partial amount --}}
            <div x-show="paymentStatus === 'partial'" class="mb-3">
                <label class="fl text-xs">المبلغ المدفوع (ر.ي) <span class="freq">*</span></label>
                <input type="number" name="paid_amount" x-model="paidAmount" step="0.01" min="0" :max="totalAmount" placeholder="0.00" class="fi">
            </div>
            <template x-if="paymentStatus === 'paid'">
                <input type="hidden" name="paid_amount" :value="totalAmount">
            </template>
            {{-- Payment method --}}
            <div class="flex gap-4 mb-3">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" name="payment_method" value="cash" x-model="paymentMethod" class="text-blue-600">
                    <span class="text-sm font-medium text-gray-700">نقدي</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" name="payment_method" value="bank_transfer" x-model="paymentMethod">
                    <span class="text-sm font-medium text-gray-700">تحويل بنكي</span>
                </label>
            </div>

            {{-- Currency notes --}}
            <div class="mb-3">
                <label class="fl text-xs">ملاحظة العملة (اختياري)</label>
                <input type="text" name="payment_notes"
                       placeholder="مثال: 100 ر.س × 400 = 40,000 ر.ي"
                       class="fi text-sm">
            </div>

            {{-- Bank transfer details --}}
            <div x-show="paymentMethod === 'bank_transfer'" x-cloak
                 class="rounded-xl p-3 space-y-2.5 border border-blue-200 bg-blue-50">
                <p class="text-xs font-bold text-blue-800">بيانات التحويل البنكي</p>
                <div>
                    <label class="fl text-xs">رقم السند / المرجع</label>
                    <input type="text" name="bank_transfer_ref" placeholder="أدخل رقم السند" class="fi text-sm bg-white">
                </div>
                <div x-data="{ receiptName: '' }">
                    <label class="fl text-xs">سند التحويل (صورة/PDF)</label>
                    <label class="flex items-center gap-2.5 cursor-pointer border-2 border-dashed border-blue-300 rounded-xl p-3 hover:border-blue-500 hover:bg-blue-100 transition">
                        <input type="file" name="bank_receipt" accept="image/*,.pdf" class="hidden"
                               @change="receiptName = $event.target.files[0]?.name ?? ''">
                        <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-blue-800" x-text="receiptName || 'انقر لرفع السند'"></p>
                            <p class="text-xs text-blue-500 mt-0.5" x-show="!receiptName">JPG · PNG · PDF</p>
                            <p class="text-xs text-emerald-600 mt-0.5 font-medium" x-show="receiptName">تم الاختيار ✓</p>
                        </div>
                    </label>
                </div>
            </div>
        </div>

        {{-- Balance summary --}}
        <div x-show="totalAmount > 0" x-transition class="rounded-xl overflow-hidden border" style="border-color: var(--navy-l)">
            <div class="px-3 py-2 text-xs font-bold text-white" style="background:var(--navy)">ملخص المدفوعات</div>
            <div class="px-3 py-2 space-y-1.5 text-sm bg-blue-50">
                <div class="flex justify-between text-gray-600">
                    <span>الإجمالي</span>
                    <span class="font-bold" style="color:var(--navy-d)" x-text="formatNumber(totalAmount) + ' ر.ي'"></span>
                </div>
                <div class="flex justify-between text-gray-600">
                    <span>المدفوع</span>
                    <span class="font-semibold text-emerald-700" x-text="formatNumber(effectivePaid) + ' ر.ي'"></span>
                </div>
                <div class="flex justify-between pt-1.5 border-t border-blue-200 font-bold">
                    <span style="color:var(--navy-d)">المتبقي</span>
                    <span :class="(totalAmount - effectivePaid) > 0 ? 'text-red-600' : 'text-emerald-600'"
                          x-text="formatNumber(totalAmount - effectivePaid) + ' ر.ي'"></span>
                </div>
            </div>
        </div>

        {{-- Submit Button --}}
        <button type="submit" :disabled="submitting" class="btn-submit">
            <template x-if="!submitting">
                <div class="flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>{{ $mode === 'checkin' ? 'تأكيد تسجيل الدخول' : 'تأكيد الحجز' }}</span>
                </div>
            </template>
            <template x-if="submitting">
                <div class="flex items-center justify-center gap-2">
                    <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    <span>جارٍ الحفظ...</span>
                </div>
            </template>
        </button>

    </div>{{-- /bpanel-body --}}
</div>{{-- /bpanel --}}
</div>{{-- /right col --}}

</div>{{-- /grid --}}
</form>
</div>{{-- /x-data --}}
@endsection

@push('scripts')
@if(session('success'))
<script>sessionStorage.removeItem('hotel_checkin_form');</script>
@endif
<script>
const CHECKIN_SESSION_KEY = 'hotel_checkin_form';
const HAS_BACKEND_ERRORS  = {{ $errors->any() ? 'true' : 'false' }};
const BOOKING_MODE        = '{{ $mode }}';

function checkInForm() {
    return {
        guestData: { full_name:'', nationality:'', occupation:'', origin:'', purpose:'', id_type:'national_id', id_number:'', id_issuer:'', id_issue_date:'', phone:'', notes:'' },
        companions: [],
        selectedRoom: null,
        linkedInfo: null,
        roomId: '',
        suiteBookingType: 'a_only',
        checkInDate: '',
        checkInTime: '',
        checkOutDate: '',
        nights: 0,
        nightsInput: 1,
        totalAmount: 0,
        customPrice: null,
        currency: 'YER',
        paymentStatus: 'paid',
        paymentMethod: 'cash',
        paidAmount: 0,
        showPriceOverride: false,
        idImagePreview: null,
        idImageName: '',
        floorFilter: 'all',
        typeFilter: 'all',
        idTypeLabel: { national_id:'هوية وطنية', passport:'جواز سفر', residence:'إقامة' },
        submitting: false,

        init() {
            const today = new Date().toISOString().split('T')[0];
            this.checkInDate = today;
            const tomorrow = new Date(); tomorrow.setDate(tomorrow.getDate() + 1);
            this.checkOutDate = tomorrow.toISOString().split('T')[0];
            if (BOOKING_MODE === 'checkin') {
                const now = new Date();
                this.checkInTime = now.toTimeString().slice(0, 5);
            }
            if (HAS_BACKEND_ERRORS) {
                this.restoreFromSession();
            } else {
                sessionStorage.removeItem(CHECKIN_SESSION_KEY);
            }
            this.$nextTick(() => this.calcTotal());
        },

        saveToSession() {
            try {
                sessionStorage.setItem(CHECKIN_SESSION_KEY, JSON.stringify({
                    guestData:       this.guestData,
                    companions:      this.companions.map(c => ({ ...c, id_preview: null })),
                    roomId:          this.roomId,
                    selectedRoom:    this.selectedRoom,
                    linkedInfo:      this.linkedInfo,
                    suiteBookingType:this.suiteBookingType,
                    checkInDate:     this.checkInDate,
                    checkInTime:     this.checkInTime,
                    checkOutDate:    this.checkOutDate,
                    nightsInput:     this.nightsInput,
                    paymentStatus:   this.paymentStatus,
                    paymentMethod:   this.paymentMethod,
                    paidAmount:      this.paidAmount,
                    customPrice:     this.customPrice,
                    showPriceOverride: this.showPriceOverride,
                }));
            } catch(e) {}
        },

        restoreFromSession() {
            try {
                const raw = sessionStorage.getItem(CHECKIN_SESSION_KEY);
                if (!raw) return;
                const s = JSON.parse(raw);
                this.guestData         = s.guestData         ?? this.guestData;
                this.companions        = s.companions         ?? [];
                this.roomId            = s.roomId             ?? '';
                this.selectedRoom      = s.selectedRoom       ?? null;
                this.linkedInfo        = s.linkedInfo         ?? null;
                this.suiteBookingType  = s.suiteBookingType   ?? 'a_only';
                this.checkInDate       = s.checkInDate        || this.checkInDate;
                this.checkInTime       = s.checkInTime        || this.checkInTime;
                this.checkOutDate      = s.checkOutDate       || this.checkOutDate;
                this.nightsInput       = s.nightsInput        ?? 1;
                this.paymentStatus     = s.paymentStatus      ?? 'paid';
                this.paymentMethod     = s.paymentMethod      ?? 'cash';
                this.paidAmount        = s.paidAmount         ?? 0;
                this.customPrice       = s.customPrice        ?? null;
                this.showPriceOverride = s.showPriceOverride  ?? false;
                this.$nextTick(() => this.calcTotal());
            } catch(e) {}
        },

        today() {
            return new Date().toISOString().split('T')[0];
        },

        addCompanion() {
            this.companions.push({ full_name:'', nationality:'', id_type:'national_id', id_number:'', id_issuer:'', id_issue_date:'', relationship:'other', id_preview:null });
        },

        removeCompanion(idx) {
            this.companions.splice(idx, 1);
        },

        selectRoom(room, info, forcedType = null) {
            this.selectedRoom = room;
            this.linkedInfo   = info || null;
            this.roomId       = room.id;
            if (forcedType) {
                this.suiteBookingType = forcedType;
            } else if (info && !info.is_always_linked) {
                this.suiteBookingType = room.sub_type === 'suite_a' ? 'a_only' : 'b_only';
            } else if (info && info.is_always_linked) {
                this.suiteBookingType = 'both';
            } else {
                this.suiteBookingType = '';
            }
            this.calcTotal();
            this.saveToSession();
        },

        clearRoomSelection() {
            this.selectedRoom    = null;
            this.linkedInfo      = null;
            this.roomId          = '';
            this.suiteBookingType = 'a_only';
            this.totalAmount     = 0;
            this.nights          = 0;
            this.saveToSession();
        },

        roomBasePriceFor(cur) {
            if (!this.selectedRoom) return 0;
            const prices = this.selectedRoom.prices || {};
            let val = parseFloat(prices['YER']);
            if (isNaN(val) || val <= 0) val = parseFloat(this.selectedRoom.base_price) || 0;
            return isNaN(val) ? 0 : val;
        },

        effectiveRoomPrice() {
            if (this.customPrice !== null && this.customPrice !== '') return parseFloat(this.customPrice) || 0;
            if (this.suiteBookingType === 'both') {
                const sp = parseFloat(this.selectedRoom?.suite_price_yer) || 0;
                if (sp > 0) return sp;
                return this.roomBasePriceFor('YER') * 2;
            }
            return this.roomBasePriceFor('YER');
        },

        roomSelectionLabel() {
            if (!this.selectedRoom) return '';
            if (this.suiteBookingType === 'both')       return 'جناح ' + this.selectedRoom.room_number + ' + ' + (this.linkedInfo?.linked_number ?? '');
            if (this.linkedInfo?.is_always_linked)      return 'شقة '  + this.selectedRoom.room_number + ' + ' + (this.linkedInfo?.linked_number ?? '');
            return 'غرفة ' + this.selectedRoom.room_number;
        },

        updateCheckoutFromNights() {
            if (this.checkInDate && this.nightsInput > 0) {
                const d = new Date(this.checkInDate);
                d.setDate(d.getDate() + parseInt(this.nightsInput));
                this.checkOutDate = d.toISOString().split('T')[0];
                this.calcTotal();
            }
        },

        calcTotal() {
            if (this.checkInDate && this.checkOutDate) {
                const d1 = new Date(this.checkInDate), d2 = new Date(this.checkOutDate);
                this.nights     = Math.max(0, Math.floor((d2 - d1) / 86400000));
                this.nightsInput = this.nights || 1;
                this.totalAmount = this.selectedRoom ? this.nights * this.effectiveRoomPrice() : 0;
                this.saveToSession();
            }
        },

        get effectivePaid() {
            if (this.paymentStatus === 'paid')    return this.totalAmount;
            if (this.paymentStatus === 'partial') return parseFloat(this.paidAmount) || 0;
            return 0;
        },

        formatNumber(n) {
            return (parseFloat(n) || 0).toLocaleString('ar-YE');
        },

        handleIdImageChange(e) {
            const file = e.target.files[0];
            if (!file) return;
            this.idImageName = file.name;
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = (ev) => this.idImagePreview = ev.target.result;
                reader.readAsDataURL(file);
            } else {
                this.idImagePreview = '__pdf__';
            }
        },

        handleIdImageDrop(e) {
            const file = e.dataTransfer.files[0];
            if (!file) return;
            const input = document.querySelector('input[name="id_image"]');
            if (input) { const dt = new DataTransfer(); dt.items.add(file); input.files = dt.files; }
            this.handleIdImageChange({ target: { files: [file] } });
        },

        handleCompanionIdImage(e, idx) {
            const file = e.target.files[0];
            if (!file) return;
            this.companions[idx].id_file_selected = true;
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = (ev) => this.companions[idx].id_preview = ev.target.result;
                reader.readAsDataURL(file);
            } else {
                this.companions[idx].id_preview = '__pdf__';
            }
        },
    };
}
</script>
@endpush
