@props(['headers' => [], 'rows' => [], 'actions' => null])

<!-- Desktop Table View -->
<div class="hidden md:block overflow-x-auto">
    <table class="w-full text-sm ui-dense" dir="rtl">
        <thead class="ui-thead">
            <tr>
                @foreach($headers as $header)
                <th class="ui-th">{{ $header }}</th>
                @endforeach
                @if($actions)
                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500">إجراءات</th>
                @endif
            </tr>
        </thead>
        <tbody class="ui-divide">
            {{ $slot }}
        </tbody>
    </table>
</div>

<!-- Mobile Card View -->
<div class="md:hidden space-y-3">
    {{ $slot }}
</div>
