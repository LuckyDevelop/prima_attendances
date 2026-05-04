{{--
    Event-based modal. Open via: $dispatch('open-modal', { name: 'modal-name' })
    Or from Livewire: $this->dispatch('open-modal', name: 'modal-name');
    Close via: $dispatch('close-modal', { name: 'modal-name' })

    Usage:
    <x-ui.modal name="create-department" title="Tambah Departemen">
        ...content...
    </x-ui.modal>
--}}

@props([
    'name'     => '',
    'title'    => '',
    'maxWidth' => 'md',    {{-- sm | md | lg | xl | 2xl --}}
])

@php
$maxWidths = [
    'sm'  => 'max-w-sm',
    'md'  => 'max-w-md',
    'lg'  => 'max-w-lg',
    'xl'  => 'max-w-xl',
    '2xl' => 'max-w-2xl',
];
$maxW = $maxWidths[$maxWidth] ?? $maxWidths['md'];
@endphp

<div x-data="{ show: false }"
     x-on:open-modal.window="$event.detail.name === '{{ $name }}' && (show = true)"
     x-on:close-modal.window="$event.detail.name === '{{ $name }}' && (show = false)"
     x-on:keydown.escape.window="show = false"
>
    <template x-teleport="body">
        <div x-show="show"
             x-transition:enter="ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 flex items-center justify-center p-4"
             style="display: none;">

            {{-- Backdrop --}}
            <div @click="show = false" class="absolute inset-0 bg-black/50"></div>

            {{-- Panel --}}
            <div @click.stop
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 class="relative w-full {{ $maxW }} rounded-xl bg-white shadow-xl">

                @if($title)
                <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                    <h3 class="text-base font-semibold text-gray-900">{{ $title }}</h3>
                    <button @click="show = false"
                            class="rounded-md p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                @endif

                <div class="px-6 py-5">{{ $slot }}</div>

                @isset($footer)
                <div class="flex justify-end gap-2 border-t border-gray-200 px-6 py-4">
                    {{ $footer }}
                </div>
                @endisset
            </div>
        </div>
    </template>
</div>
