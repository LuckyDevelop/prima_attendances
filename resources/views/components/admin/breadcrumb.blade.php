@props(['items' => []])

@if(count($items))
<nav class="mb-4 flex" aria-label="Breadcrumb">
    <ol class="flex items-center space-x-2">
        <li>
            <a href="{{ route('admin.dashboard') }}"
               class="text-sm text-gray-400 hover:text-gray-600">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                </svg>
            </a>
        </li>
        @foreach($items as $item)
        <li class="flex items-center">
            <svg class="h-4 w-4 flex-shrink-0 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                <path d="M5.555 17.776l8-16 .894.448-8 16-.894-.448z" />
            </svg>
            @if(!$loop->last && isset($item['url']))
            <a href="{{ $item['url'] }}"
               class="ml-2 text-sm text-gray-400 hover:text-gray-600">
                {{ $item['label'] }}
            </a>
            @else
            <span class="ml-2 text-sm font-medium text-gray-700">{{ $item['label'] }}</span>
            @endif
        </li>
        @endforeach
    </ol>
</nav>
@endif
