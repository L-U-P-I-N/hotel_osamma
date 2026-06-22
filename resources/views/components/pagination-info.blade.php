@props(['items'])

@php
    $from = $items->count() > 0 ? (($items->currentPage() - 1) * $items->perPage()) + 1 : 0;
    $to = min($from + $items->count() - 1, $items->total());
    $total = $items->total();
@endphp

@if($total > 0)
<div class="text-xs text-gray-500 text-center py-2">
    عرض من <strong>{{ $from }}</strong> إلى <strong>{{ $to }}</strong> من إجمالي <strong>{{ $total }}</strong>
</div>
@endif
