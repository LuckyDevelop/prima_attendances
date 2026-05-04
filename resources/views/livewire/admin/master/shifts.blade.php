<div>
    <x-admin.page-header title="Shift Kerja">
        <x-slot:actions>
            <x-ui.button wire:click="openCreate">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Tambah Shift
            </x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.data-table :headers="['Nama Shift', 'Jam Mulai', 'Jam Selesai', 'Istirahat', 'Total Kerja', 'Aksi']">
        @forelse($shifts as $shift)
        <tr wire:key="shift-{{ $shift->id }}">
            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $shift->name }}</td>
            <td class="px-6 py-4 text-sm text-gray-600 font-mono">
                {{ substr((string) $shift->start_time, 0, 5) }}
            </td>
            <td class="px-6 py-4 text-sm text-gray-600 font-mono">
                {{ substr((string) $shift->end_time, 0, 5) }}
            </td>
            <td class="px-6 py-4 text-sm text-gray-600">{{ $shift->break_minutes }} menit</td>
            <td class="px-6 py-4 text-sm text-gray-600">
                @php
                    [$sh, $sm] = array_map('intval', explode(':', substr((string)$shift->start_time, 0, 5)));
                    [$eh, $em] = array_map('intval', explode(':', substr((string)$shift->end_time, 0, 5)));
                    $net = max(0, ($eh * 60 + $em) - ($sh * 60 + $sm) - $shift->break_minutes);
                @endphp
                {{ floor($net / 60) }}j {{ $net % 60 }}m
            </td>
            <td class="px-6 py-4 text-right">
                <div class="flex items-center justify-end gap-3">
                    <button wire:click="openEdit({{ $shift->id }})"
                            class="text-sm text-blue-600 hover:underline">Edit</button>
                    <button wire:click="delete({{ $shift->id }})"
                            wire:confirm="Hapus shift '{{ $shift->name }}'?"
                            class="text-sm text-red-600 hover:underline">Hapus</button>
                </div>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="6" class="px-6 py-12">
                <x-admin.empty-state title="Belum ada shift" description="Tambahkan shift kerja untuk perusahaan Anda." />
            </td>
        </tr>
        @endforelse
    </x-admin.data-table>

    {{-- Modal --}}
    <x-ui.modal name="shift-form" max-width="md">
        <x-slot:title>{{ $editingId ? 'Edit Shift' : 'Tambah Shift' }}</x-slot:title>

        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Nama Shift <span class="text-red-500">*</span>
                </label>
                <input wire:model="name" type="text"
                       class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                       placeholder="Contoh: Shift Pagi, Shift Malam" />
                @error('name')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Jam Mulai <span class="text-red-500">*</span>
                    </label>
                    <input wire:model.live="startTime" type="time"
                           class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                    @error('startTime')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Jam Selesai <span class="text-red-500">*</span>
                    </label>
                    <input wire:model.live="endTime" type="time"
                           class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                    @error('endTime')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Istirahat (menit) <span class="text-red-500">*</span>
                </label>
                <input wire:model.live="breakMinutes" type="number" min="0" max="480"
                       class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                @error('breakMinutes')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            @if($workMinutes > 0)
            <div class="rounded-lg bg-blue-50 px-4 py-3 text-sm text-blue-700">
                Total jam kerja efektif:
                <strong>{{ floor($workMinutes / 60) }} jam {{ $workMinutes % 60 }} menit</strong>
            </div>
            @endif
        </div>

        <x-slot:footer>
            <button @click="$dispatch('close-modal', { name: 'shift-form' })"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                Batal
            </button>
            <x-ui.button wire:click="save">Simpan</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
