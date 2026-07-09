@extends('layouts.app')
@section('title', 'مرافقو الحجز #' . $reservation->id)
@section('page-title', 'المرافقون')
@section('back-url', route('reservations.show', $reservation))

@push('styles')
<style>
.badge-pill { display: inline-flex; align-items: center; gap: 4px; padding: 4px 12px; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; }
</style>
@endpush

@section('content')
<div class="max-w-4xl mx-auto space-y-5" dir="rtl">

    {{-- Header --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex items-center justify-between gap-4 flex-wrap">
        <div class="flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-violet-600 flex items-center justify-center shadow-sm">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            <div>
                <h2 class="font-bold text-gray-800 text-lg leading-tight">مرافقو {{ $reservation->guest?->full_name ?? 'النزيل' }}</h2>
                <p class="text-xs text-gray-400 mt-0.5">حجز #{{ str_pad($reservation->id, 5, '0', STR_PAD_LEFT) }} — غرفة {{ $reservation->display_room_number }}</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <span class="px-3 py-1 bg-violet-100 text-violet-700 text-sm font-bold rounded-full">{{ $reservation->companions->count() }} مرافق</span>
            <a href="{{ route('reservations.show', $reservation) }}"
               class="inline-flex items-center gap-2 px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-xl hover:bg-gray-50 hover:border-gray-300 transition font-medium">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                العودة للحجز
            </a>
        </div>
    </div>

    @if($reservation->companions->isEmpty())
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 py-16 text-center text-gray-400">
        <svg class="w-12 h-12 mx-auto mb-3 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        <p class="text-sm">لا يوجد مرافقون لهذا الحجز</p>
    </div>
    @else
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @foreach($reservation->companions as $idx => $c)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-5">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-9 h-9 rounded-full bg-violet-100 text-violet-700 text-sm font-black flex items-center justify-center shrink-0">
                        {{ $idx + 1 }}
                    </div>
                    <span class="font-bold text-gray-900">{{ $c->full_name }}</span>
                    <span class="badge-pill bg-violet-50 text-violet-600">{{ $c->getRelationshipLabel() }}</span>
                </div>

                <div class="flex gap-5">
                    <div class="flex-1 grid grid-cols-2 gap-x-5 gap-y-3 text-sm">
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wide mb-0.5">الجنسية</p>
                            <p class="text-gray-700">{{ $c->nationality ?: '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wide mb-0.5">نوع الهوية</p>
                            <p class="text-gray-700">{{ $c->getIdTypeLabel() ?? '—' }}</p>
                        </div>
                        @can('guests.sensitive')
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wide mb-0.5">رقم الهوية</p>
                            <p class="font-mono font-semibold text-gray-800">{{ $c->id_number ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wide mb-0.5">جهة الإصدار</p>
                            <p class="text-gray-700">{{ $c->id_issuer ?: '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wide mb-0.5">تاريخ الإصدار</p>
                            <p class="text-gray-700">{{ $c->id_issue_date?->format('d/m/Y') ?: '—' }}</p>
                        </div>
                        @endcan
                    </div>

                    <div class="flex gap-3 md:flex-col md:w-32 shrink-0">
                        @if($c->id_image_path)
                        <div class="flex-1 md:flex-none">
                            <p class="text-xs text-gray-400 mb-1.5 text-center font-medium">صورة الهوية</p>
                            @php $cExt = strtolower(pathinfo($c->id_image_path, PATHINFO_EXTENSION)); @endphp
                            @if($cExt === 'pdf')
                            <a href="{{ route('companions.idImage', $c) }}" target="_blank"
                               class="flex flex-col items-center justify-center w-full h-24 border-2 border-dashed border-gray-200 rounded-xl hover:border-blue-400 transition bg-red-50">
                                <svg class="w-7 h-7 text-red-400" fill="currentColor" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zm-1 1.5L18.5 9H13V3.5z"/></svg>
                                <span class="text-xs text-red-400 mt-1 font-medium">PDF</span>
                            </a>
                            @else
                            <a href="{{ route('companions.idImage', $c) }}" target="_blank">
                                <img src="{{ route('companions.idImage', $c) }}" alt="هوية المرافق"
                                     class="w-full h-24 object-cover rounded-xl border border-gray-200 hover:opacity-90 transition cursor-zoom-in shadow-sm">
                            </a>
                            @endif
                        </div>
                        @endif
                        @if($c->marriage_doc_path)
                        <div class="flex-1 md:flex-none">
                            <p class="text-xs text-gray-400 mb-1.5 text-center font-medium">عقد الزواج</p>
                            @php $mExt = strtolower(pathinfo($c->marriage_doc_path, PATHINFO_EXTENSION)); @endphp
                            @if($mExt === 'pdf')
                            <a href="{{ route('companions.marriageDoc', $c) }}" target="_blank"
                               class="flex flex-col items-center justify-center w-full h-24 border-2 border-dashed border-gray-200 rounded-xl hover:border-pink-400 transition bg-pink-50">
                                <svg class="w-7 h-7 text-pink-400" fill="currentColor" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zm-1 1.5L18.5 9H13V3.5z"/></svg>
                                <span class="text-xs text-pink-400 mt-1 font-medium">PDF</span>
                            </a>
                            @else
                            <a href="{{ route('companions.marriageDoc', $c) }}" target="_blank">
                                <img src="{{ route('companions.marriageDoc', $c) }}" alt="عقد الزواج"
                                     class="w-full h-24 object-cover rounded-xl border border-gray-200 hover:opacity-90 transition cursor-zoom-in shadow-sm">
                            </a>
                            @endif
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

</div>
@endsection
