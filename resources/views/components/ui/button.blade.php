@props([
    'variant' => 'primary',  {{-- primary | secondary | danger | ghost --}}
    'size'    => 'md',        {{-- sm | md | lg --}}
    'type'    => 'button',
    'href'    => null,
])

@php
$base = 'inline-flex items-center justify-center gap-2 rounded-lg font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed';

$variants = [
    'primary'   => 'bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500',
    'secondary' => 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 focus:ring-blue-500',
    'danger'    => 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500',
    'ghost'     => 'text-gray-600 hover:bg-gray-100 hover:text-gray-900 focus:ring-gray-500',
];

$sizes = [
    'sm' => 'px-3 py-1.5 text-xs',
    'md' => 'px-4 py-2 text-sm',
    'lg' => 'px-5 py-2.5 text-base',
];

$classes = $base.' '.($variants[$variant] ?? $variants['primary']).' '.($sizes[$size] ?? $sizes['md']);
@endphp

@if($href)
<a href="{{ $href }}" {{ $attributes->class([$classes]) }}>
    {{ $slot }}
</a>
@else
<button type="{{ $type }}" {{ $attributes->class([$classes]) }}>
    <span wire:loading.remove wire:target="{{ $attributes->get('wire:click', $attributes->get('wire:submit', '')) }}">
        {{ $slot }}
    </span>
    <span wire:loading wire:target="{{ $attributes->get('wire:click', $attributes->get('wire:submit', '')) }}"
          class="flex items-center gap-2">
        <x-ui.spinner class="h-4 w-4" />
        Memproses...
    </span>
</button>
@endif
