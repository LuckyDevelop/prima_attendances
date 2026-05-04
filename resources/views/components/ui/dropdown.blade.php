@props(['align' => 'right'])   {{-- right | left --}}

<div x-data="{ open: false }" class="relative inline-block">
    {{-- Trigger --}}
    <div @click="open = !open" @click.outside="open = false">
        {{ $trigger }}
    </div>

    {{-- Items --}}
    <div x-show="open"
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="transform opacity-0 scale-95"
         x-transition:enter-end="transform opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="transform opacity-100 scale-100"
         x-transition:leave-end="transform opacity-0 scale-95"
         @class([
             'absolute z-50 mt-1 min-w-max rounded-lg border border-gray-200 bg-white py-1 shadow-lg',
             'right-0 origin-top-right' => $align === 'right',
             'left-0 origin-top-left'   => $align === 'left',
         ])
         style="display: none;">
        {{ $slot }}
    </div>
</div>
