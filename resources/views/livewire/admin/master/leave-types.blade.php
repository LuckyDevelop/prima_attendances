<div>
    <x-admin.page-header title="Jenis Cuti">
        <x-slot:actions>
            <x-ui.button wire:click="openCreate">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Tambah Jenis Cuti
            </x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.data-table :headers="['Nama', 'Kuota Default', 'Lampiran', 'Berbayar', 'Aksi']">
        @forelse($leaveTypes as $type)
        <tr wire:key="lt-{{ $type->id }}">
            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $type->name }}</td>
            <td class="px-6 py-4 text-sm text-gray-600">{{ $type->default_quota }} hari</td>
            <td class="px-6 py-4">
                @if($type->requires_attachment)
                    <span class="inline-flex items-center gap-1 text-xs text-green-700">
                        <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        Wajib
                    </span>
                @else
                    <span class="text-xs text-gray-400">Tidak</span>
                @endif
            </td>
            <td class="px-6 py-4">
                @if($type->is_paid)
                    <x-ui.badge status="approved">Berbayar</x-ui.badge>
                @else
                    <x-ui.badge status="inactive">Tidak Berbayar</x-ui.badge>
                @endif
            </td>
            <td class="px-6 py-4 text-right">
                <div class="flex items-center justify-end gap-3">
                    <button wire:click="openEdit({{ $type->id }})"
                            class="text-sm text-blue-600 hover:underline">Edit</button>
                    <button wire:click="delete({{ $type->id }})"
                            wire:confirm="Hapus jenis cuti '{{ $type->name }}'?"
                            class="text-sm text-red-600 hover:underline">Hapus</button>
                </div>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="5" class="px-6 py-12">
                <x-admin.empty-state title="Belum ada jenis cuti"
                    description="Tambahkan jenis cuti untuk perusahaan Anda." />
            </td>
        </tr>
        @endforelse
    </x-admin.data-table>

    {{-- Modal --}}
    <x-ui.modal name="leave-type-form" max-width="md">
        <x-slot:title>{{ $editingId ? 'Edit Jenis Cuti' : 'Tambah Jenis Cuti' }}</x-slot:title>

        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Nama <span class="text-red-500">*</span>
                </label>
                <input wire:model="name" type="text"
                       class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                       placeholder="Contoh: Cuti Tahunan" />
                @error('name')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Kuota Default (hari) <span class="text-red-500">*</span>
                </label>
                <input wire:model="defaultQuota" type="number" min="0" max="365"
                       class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                @error('defaultQuota')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-3">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input wire:model="requiresAttachment" type="checkbox"
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
                    <span class="text-sm text-gray-700">Wajib melampirkan dokumen</span>
                </label>
                <label class="flex items-center gap-3 cursor-pointer">
                    <input wire:model="isPaid" type="checkbox"
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
                    <span class="text-sm text-gray-700">Cuti berbayar (paid leave)</span>
                </label>
            </div>
        </div>

        <x-slot:footer>
            <button @click="$dispatch('close-modal', { name: 'leave-type-form' })"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                Batal
            </button>
            <x-ui.button wire:click="save">Simpan</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
