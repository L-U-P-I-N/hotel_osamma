@props(['label' => 'الإجمالي', 'totals' => []])

@if(!empty($totals))
<tfoot class="bg-gray-50 border-t-2 border-gray-200">
    <tr>
        <td class="px-4 py-3 font-semibold text-gray-700">{{ $label }}</td>
        @foreach($totals as $total)
        <td class="px-4 py-3">
            <div class="text-sm font-bold text-gray-900">
                @if(is_numeric($total))
                    {{ number_format($total, 0) }}
                @else
                    {{ $total }}
                @endif
            </div>
        </td>
        @endforeach
    </tr>
</tfoot>
@endif
