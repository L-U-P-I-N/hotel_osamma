@extends('layouts.app')
@section('title', 'إدارة الطوابق')
@section('page-title', 'إدارة الطوابق')

@section('content')
<div dir="rtl" style="max-width:62rem;margin-inline:auto;" x-data="{ editId: null, editData: {} }">

    <div class="ui-head">
        <h2 class="ui-head-title">الطوابق</h2>
        <p class="ui-head-meta"><b>{{ $floors->count() }}</b> طابقاً مسجلاً</p>
    </div>

    @if(session('success'))
    <div class="ui-note ui-note--ok" style="margin-bottom:.75rem;">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="ui-note ui-note--error" style="margin-bottom:.75rem;">{{ session('error') }}</div>
    @endif

    {{-- إضافة طابق --}}
    <form method="POST" action="{{ route('floors.store') }}" class="ui-panel">
        @csrf
        <div class="ui-panel-head"><span class="ui-panel-title">إضافة طابق جديد</span></div>
        <div class="ui-panel-body">
            @if($errors->any())
            <div class="ui-note ui-note--error" style="margin-bottom:.75rem;">
                @foreach($errors->all() as $error)<div>• {{ $error }}</div>@endforeach
            </div>
            @endif

                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(10rem,1fr));gap:.75rem;">
                    <div class="ui-field" style="margin:0;">
                        <label for="f-num">رقم الطابق <span class="req">*</span></label>
                        <input id="f-num" type="number" name="floor_number" value="{{ old('floor_number') }}"
                               required min="1" max="50" class="ui-input is-mono" placeholder="مثال: 1">
                    </div>
                    <div class="ui-field" style="margin:0;">
                        <label for="f-doors">عدد الأبواب <span class="req">*</span></label>
                        <input id="f-doors" type="number" name="door_count" value="{{ old('door_count', 10) }}"
                               required min="1" max="100" class="ui-input is-mono" placeholder="مثال: 10">
                    </div>
                    <div class="ui-field" style="margin:0;">
                        <label for="f-name">الاسم (اختياري)</label>
                        <input id="f-name" type="text" name="name" value="{{ old('name') }}"
                               class="ui-input" placeholder="مثال: الطابق الأول">
                    </div>
                </div>
        </div>
        <div class="ui-actions">
            <button type="submit" class="ui-btn ui-btn--primary">إضافة الطابق</button>
        </div>
    </form>

    {{-- الطوابق --}}
    <div class="ui-panel">
        <div class="ui-panel-head"><span class="ui-panel-title">الطوابق المسجلة</span></div>

        @if($floors->isEmpty())
        <div class="ui-empty">لا توجد طوابق مسجلة بعد</div>
        @else
        <div class="ui-table-wrap">
            <table class="ui-table">
                <thead>
                    <tr>
                        <th class="num">الطابق</th>
                        <th>الاسم</th>
                        <th class="num">الأبواب</th>
                        <th>نطاق الأرقام</th>
                        <th class="num">الغرف المسجلة</th>
                        <th class="t-actions">إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($floors as $floor)
                    <tr>
                        <td class="num t-strong" style="color:var(--ui-accent);">{{ $floor->floor_number }}</td>
                        <td style="color:var(--ui-ink-2);">{{ $floor->name ?? '—' }}</td>
                        <td class="num">{{ $floor->door_count }}</td>
                        <td><span class="code">{{ $floor->floor_number * 100 + 1 }}–{{ $floor->floor_number * 100 + $floor->door_count }}</span></td>
                        <td class="num">
                            <span class="t-strong">{{ $floor->used_doors }}</span><span class="num--zero"> / {{ $floor->door_count }}</span>
                        </td>
                        <td class="t-actions">
                            @if($floor->in_maintenance)
                            <span class="ui-chip ui-chip--warn" title="كل غرف الطابق في الصيانة">في الصيانة</span>
                            @endif

                            <button type="button" class="ui-btn ui-btn--sm"
                                    @click="editId = editId === {{ $floor->id }} ? null : {{ $floor->id }}; editData = { floor_number: {{ $floor->floor_number }}, door_count: {{ $floor->door_count }}, name: '{{ $floor->name }}' }">
                                تعديل
                            </button>

                            @canany(['rooms.edit', 'rooms.maintenance'])
                            @if($floor->in_maintenance)
                            <form method="POST" action="{{ route('floors.endMaintenance', $floor) }}" style="display:inline;"
                                  onsubmit="return confirm('إنهاء صيانة الطابق {{ $floor->floor_number }} وإعادة كل غرفه إلى (متاحة)؟')">
                                @csrf
                                <button type="submit" class="ui-btn ui-btn--sm">إنهاء الصيانة</button>
                            </form>
                            @elseif($floor->has_guest)
                            <span class="ui-btn ui-btn--sm is-off"
                                  title="يوجد نزلاء/حجوزات على الغرف: {{ $floor->blocking_rooms->implode('، ') }} — يجب إخراجهم أو إلغاء الحجوزات أولاً">
                                وضع في الصيانة
                            </span>
                            @else
                            <form method="POST" action="{{ route('floors.maintenance', $floor) }}" style="display:inline;"
                                  onsubmit="return confirm('وضع كل غرف الطابق {{ $floor->floor_number }} في الصيانة؟')">
                                @csrf
                                <button type="submit" class="ui-btn ui-btn--sm">وضع في الصيانة</button>
                            </form>
                            @endif
                            @endcanany

                            <form method="POST" action="{{ route('floors.destroy', $floor) }}" style="display:inline;"
                                  onsubmit="return confirm('حذف الطابق {{ $floor->floor_number }}؟')">
                                @csrf @method('DELETE')
                                <button type="submit" class="ui-btn ui-btn--sm ui-btn--danger">حذف</button>
                            </form>
                        </td>
                    </tr>

                    {{-- سطر التحرير يفتح مكانه في الجدول، فلا يفقد المستخدم موضع الطابق --}}
                    <tr x-show="editId === {{ $floor->id }}" x-cloak class="is-selected">
                        <td colspan="6">
                            <form method="POST" action="{{ route('floors.update', $floor) }}"
                                  style="display:flex;flex-wrap:wrap;align-items:flex-end;gap:.625rem;">
                                @csrf @method('PUT')
                                <div class="ui-field" style="margin:0;width:6rem;">
                                    <label>رقم الطابق</label>
                                    <input type="number" name="floor_number" :value="editData.floor_number"
                                           required min="1" max="50" class="ui-input is-mono">
                                </div>
                                <div class="ui-field" style="margin:0;width:6rem;">
                                    <label>عدد الأبواب</label>
                                    <input type="number" name="door_count" :value="editData.door_count"
                                           required min="1" max="100" class="ui-input is-mono">
                                </div>
                                <div class="ui-field" style="margin:0;width:12rem;">
                                    <label>الاسم</label>
                                    <input type="text" name="name" :value="editData.name" class="ui-input">
                                </div>
                                <button type="submit" class="ui-btn ui-btn--primary">حفظ</button>
                                <button type="button" class="ui-btn" @click="editId = null">إلغاء</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>
@endsection
