@props([
    'title'       => 'Belum ada data',
    'description' => '',
])

<div class="flex flex-col items-center justify-center py-12 text-center">
    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-gray-100">
        @isset($icon)
        <span class="text-gray-400">{{ $icon }}</span>
        @else
        <svg class="h-8 w-8 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
        </svg>
        @endisset
    </div>

    <h3 class="text-sm font-semibold text-gray-900">{{ $title }}</h3>

    @if($description)
    <p class="mt-1 text-sm text-gray-500">{{ $description }}</p>
    @endif

    @isset($action)
    <div class="mt-4">
        {{ $action }}
    </div>
    @endisset
</div>
