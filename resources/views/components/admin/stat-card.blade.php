@props([
    'label' => '',
    'value' => '0',
    'color' => 'blue',   {{-- blue | green | yellow | red | purple --}}
    'trend' => null,     {{-- optional: '+5%' or '-2%' --}}
])

@php
$colors = [
    'blue'   => 'bg-blue-100 text-blue-700',
    'green'  => 'bg-green-100 text-green-700',
    'yellow' => 'bg-yellow-100 text-yellow-700',
    'red'    => 'bg-red-100 text-red-700',
    'purple' => 'bg-purple-100 text-purple-700',
];
$iconColor = $colors[$color] ?? $colors['blue'];
@endphp

<div class="overflow-hidden rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
    <div class="flex items-center justify-between">
        <p class="text-sm font-medium text-gray-500">{{ $label }}</p>
        @isset($icon)
        <div class="rounded-lg p-2 {{ $iconColor }}">
            {{ $icon }}
        </div>
        @endisset
    </div>
    <div class="mt-3 flex items-end justify-between">
        <p class="text-2xl font-bold text-gray-900">{{ $value }}</p>
        @if($trend)
        <span class="text-xs font-medium text-gray-500">{{ $trend }}</span>
        @endif
    </div>
</div>
