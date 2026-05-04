@props([
    'href'   => '#',
    'active' => false,
    'label'  => '',
])

<a href="{{ $href }}"
   class="group relative flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors"
   :class="sidebarOpen ? '' : 'lg:justify-center'"
   @class([
       'bg-gray-800 text-white'                    => $active,
       'text-gray-400 hover:bg-gray-700 hover:text-white' => ! $active,
   ])
>
    <span class="flex-shrink-0">
        {{ $icon }}
    </span>

    {{-- Label — hidden when sidebar collapsed on desktop --}}
    <span class="overflow-hidden whitespace-nowrap transition-all duration-200"
          :class="sidebarOpen ? 'block' : 'lg:hidden'">
        {{ $label }}
    </span>

    {{-- Tooltip shown when collapsed on desktop --}}
    <span x-show="!sidebarOpen"
          class="pointer-events-none absolute left-full z-50 ml-2 hidden whitespace-nowrap rounded border border-gray-700 bg-gray-900 px-2 py-1 text-xs text-white opacity-0 transition-opacity group-hover:opacity-100 lg:block">
        {{ $label }}
    </span>
</a>
