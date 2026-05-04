@props([
    'status' => 'default',
    'size'   => 'sm',   {{-- sm | md --}}
])

@php
$colors = [
    'present'   => 'bg-green-100 text-green-800',
    'approved'  => 'bg-green-100 text-green-800',
    'active'    => 'bg-green-100 text-green-800',
    'pending'   => 'bg-yellow-100 text-yellow-800',
    'late'      => 'bg-orange-100 text-orange-800',
    'absent'    => 'bg-red-100 text-red-800',
    'rejected'  => 'bg-red-100 text-red-800',
    'inactive'  => 'bg-gray-100 text-gray-700',
    'cancelled' => 'bg-gray-100 text-gray-700',
    'leave'     => 'bg-blue-100 text-blue-800',
    'info'      => 'bg-blue-100 text-blue-800',
    'warning'   => 'bg-yellow-100 text-yellow-800',
    'error'     => 'bg-red-100 text-red-800',
    'default'   => 'bg-gray-100 text-gray-700',
];

$sizes = [
    'sm' => 'px-2 py-0.5 text-xs',
    'md' => 'px-2.5 py-1 text-sm',
];

$color = $colors[$status] ?? $colors['default'];
$sizeClass = $sizes[$size] ?? $sizes['sm'];
@endphp

<span {{ $attributes->class(["inline-flex items-center rounded-full font-medium $color $sizeClass"]) }}>
    {{ $slot ?: ucfirst($status) }}
</span>
