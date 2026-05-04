@props([
    'title'       => '',
    'description' => '',
])

<div class="mb-6 flex items-start justify-between gap-4">
    <div>
        <h1 class="text-xl font-semibold text-gray-900">{{ $title }}</h1>
        @if($description)
        <p class="mt-1 text-sm text-gray-500">{{ $description }}</p>
        @endif
    </div>

    @isset($actions)
    <div class="flex flex-shrink-0 items-center gap-2">
        {{ $actions }}
    </div>
    @endisset
</div>
