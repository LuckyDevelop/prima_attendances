@props(['padding' => true])

<div {{ $attributes->class(['overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm']) }}>
    @isset($header)
    <div class="border-b border-gray-200 px-6 py-4">
        {{ $header }}
    </div>
    @endisset

    <div @class(['px-6 py-5' => $padding])>
        {{ $slot }}
    </div>

    @isset($footer)
    <div class="border-t border-gray-200 bg-gray-50 px-6 py-4">
        {{ $footer }}
    </div>
    @endisset
</div>
