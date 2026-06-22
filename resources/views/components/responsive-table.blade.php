@props(['headers' => [], 'rows' => [], 'actions' => null])

<!-- Desktop Table View -->
<div class="hidden md:block overflow-x-auto">
    <table class="w-full text-sm" dir="rtl">
        <thead class="bg-gray-50">
            <tr>
                @foreach($headers as $header)
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">{{ $header }}</th>
                @endforeach
                @if($actions)
                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500">إجراءات</th>
                @endif
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            {{ $slot }}
        </tbody>
    </table>
</div>

<!-- Mobile Card View -->
<div class="md:hidden space-y-3">
    {{ $slot }}
</div>
