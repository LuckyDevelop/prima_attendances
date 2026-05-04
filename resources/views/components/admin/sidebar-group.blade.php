@props(['label'])

<div class="mt-4">
    <p class="mb-1 px-3 text-xs font-semibold uppercase tracking-wider text-gray-500 transition-all duration-200"
       :class="sidebarOpen ? 'opacity-100' : 'lg:opacity-0 lg:h-0 lg:overflow-hidden lg:mb-0'">
        {{ $label }}
    </p>
    <div class="space-y-0.5">
        {{ $slot }}
    </div>
</div>
