{{--
    Usage:
    <x-admin.confirm-modal
        name="delete-user"
        title="Hapus Karyawan?"
        message="Tindakan ini tidak dapat dibatalkan."
        confirm-text="Hapus"
        danger
    />

    Open from Livewire: $this->dispatch('open-modal', name: 'delete-user');
    Confirm fires:     $this->dispatch('confirmed-delete-user');
--}}

@props([
    'name'        => '',
    'title'       => 'Konfirmasi',
    'message'     => 'Apakah Anda yakin?',
    'confirmText' => 'Konfirmasi',
    'cancelText'  => 'Batal',
    'danger'      => false,
])

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
                 x-transition:enter-start="opacity-0 translate-y-4"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="relative w-full max-w-md rounded-xl bg-white p-6 shadow-xl">

                <div class="flex items-start gap-4">
                    @if($danger)
                    <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-red-100">
                        <svg class="h-5 w-5 text-red-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                    </div>
                    @endif

                    <div class="flex-1">
                        <h3 class="text-base font-semibold text-gray-900">{{ $title }}</h3>
                        <p class="mt-1 text-sm text-gray-500">{{ $message }}</p>

                        @isset($slot)
                        <div class="mt-3">{{ $slot }}</div>
                        @endisset
                    </div>
                </div>

                <div class="mt-5 flex justify-end gap-2">
                    <button @click="show = false"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        {{ $cancelText }}
                    </button>
                    <button @click="$dispatch('{{ 'confirmed-'.$name }}'); show = false"
                            @class([
                                'rounded-lg px-4 py-2 text-sm font-medium text-white',
                                'bg-red-600 hover:bg-red-700' => $danger,
                                'bg-blue-600 hover:bg-blue-700' => ! $danger,
                            ])>
                        {{ $confirmText }}
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>
