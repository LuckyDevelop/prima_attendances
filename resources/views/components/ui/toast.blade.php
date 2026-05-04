{{--
    Global toast container. Already included in layouts/admin.blade.php.
    Trigger from Livewire PHP: $this->dispatch('toast', type: 'success', message: 'Berhasil!');
    Trigger from Alpine JS: $dispatch('toast', { type: 'error', message: 'Gagal!' });
    Types: success | error | warning | info
--}}

<div x-data="{
        toasts: [],
        add(event) {
            const id = Date.now();
            this.toasts.push({ id, type: event.type ?? 'info', message: event.message ?? '' });
            setTimeout(() => this.remove(id), 4000);
        },
        remove(id) {
            this.toasts = this.toasts.filter(t => t.id !== id);
        }
     }"
     x-on:toast.window="add($event.detail)"
     class="fixed bottom-4 right-4 z-[60] flex flex-col gap-2"
     style="pointer-events: none;">

    <template x-for="toast in toasts" :key="toast.id">
        <div x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="transform opacity-0 translate-y-2"
             x-transition:enter-end="transform opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="flex items-start gap-3 rounded-lg border px-4 py-3 shadow-lg"
             :class="{
                 'bg-green-50 border-green-200 text-green-800': toast.type === 'success',
                 'bg-red-50 border-red-200 text-red-800':     toast.type === 'error',
                 'bg-yellow-50 border-yellow-200 text-yellow-800': toast.type === 'warning',
                 'bg-blue-50 border-blue-200 text-blue-800':  toast.type === 'info',
             }"
             style="min-width: 260px; max-width: 380px; pointer-events: auto;">

            {{-- Icon --}}
            <span class="mt-0.5 flex-shrink-0">
                <template x-if="toast.type === 'success'">
                    <svg class="h-5 w-5 text-green-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </template>
                <template x-if="toast.type === 'error'">
                    <svg class="h-5 w-5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </template>
                <template x-if="toast.type === 'warning'">
                    <svg class="h-5 w-5 text-yellow-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                    </svg>
                </template>
                <template x-if="toast.type === 'info'">
                    <svg class="h-5 w-5 text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
                    </svg>
                </template>
            </span>

            {{-- Message --}}
            <p class="flex-1 text-sm font-medium" x-text="toast.message"></p>

            {{-- Close --}}
            <button @click="remove(toast.id)"
                    class="flex-shrink-0 rounded p-0.5 opacity-60 hover:opacity-100">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </template>
</div>
