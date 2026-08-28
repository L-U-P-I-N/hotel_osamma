@foreach($nodes as $node)
<div>
    <a href="{{ route('reports.financeHub', array_merge(request()->only(['from','to','month','period']), ['tab' => 'accounts', 'account_id' => $node->id])) }}"
       class="flex items-center justify-between py-1.5 px-2 rounded-lg hover:bg-gray-50 text-sm
              {{ request('account_id') == $node->id ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-gray-700' }}"
       style="padding-right: {{ 8 + $depth * 18 }}px;">
        <span>{{ $node->code }} — {{ $node->name }}</span>
        <span class="font-mono {{ $node->effective_balance < 0 ? 'text-red-600' : 'text-gray-500' }}">
            {{ number_format($node->effective_balance, 0) }}
        </span>
    </a>
    @if($node->childNodes->isNotEmpty())
        @include('reports.partials.account-tree-node', ['nodes' => $node->childNodes, 'depth' => $depth + 1])
    @endif
</div>
@endforeach
