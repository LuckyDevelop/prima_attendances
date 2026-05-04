@props([
    'name' => '',
    'src'  => null,
])

@php
$initials = collect(explode(' ', $name))
    ->take(2)
    ->map(fn($word) => strtoupper(substr($word, 0, 1)))
    ->implode('');

$colors = ['bg-blue-500', 'bg-green-500', 'bg-purple-500', 'bg-orange-500', 'bg-pink-500', 'bg-teal-500'];
$color  = $colors[crc32($name) % count($colors)];
@endphp

<div {{ $attributes->class(['relative flex-shrink-0 overflow-hidden rounded-full']) }}>
    @if($src)
    <img src="{{ $src }}" alt="{{ $name }}" class="h-full w-full object-cover">
    @else
    <div class="flex h-full w-full items-center justify-center {{ $color }} text-white text-xs font-semibold">
        {{ $initials ?: '?' }}
    </div>
    @endif
</div>
