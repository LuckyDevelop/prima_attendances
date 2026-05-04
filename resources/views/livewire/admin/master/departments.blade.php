<div>
    <x-admin.page-header title="Departemen">
        <x-slot:actions>
            <x-ui.button wire:click="openCreate">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Tambah Departemen
            </x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.data-table :headers="['Nama Departemen', 'Manager', 'Jumlah Karyawan', 'Aksi']">
        @forelse($departments as $dept)
        <tr wire:key="dept-{{ $dept->id }}">
            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $dept->name }}</td>
            <td class="px-6 py-4 text-sm text-gray-600">{{ $dept->manager?->full_name ?? '—' }}</td>
            <td class="px-6 py-4 text-sm text-gray-600">{{ $dept->users_count }}</td>
            <td class="px-6 py-4 text-right">
                <div class="flex items-center justify-end gap-3">
                    <button wire:click="openEdit({{ $dept->id }})"
                            class="text-sm text-blue-600 hover:underline">Edit</button>
                    <button wire:click="delete({{ $dept->id }})"
                            wire:confirm="Hapus departemen '{{ $dept->name }}'?"
                            class="text-sm text-red-600 hover:underline">Hapus</button>
                </div>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="4" class="px-6 py-12">
                <x-admin.empty-state title="Belum ada departemen"
                    description="Tambahkan departemen untuk perusahaan Anda." />
            </td>
        </tr>
        @endforelse
    </x-admin.data-table>

    {{-- Modal --}}
    <x-ui.modal name="dept-form" max-width="md">
        <x-slot:title>{{ $editingId ? 'Edit Departemen' : 'Tambah Departemen' }}</x-slot:title>

        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Nama Departemen <span class="text-red-500">*</span>
                </label>
                <input wire:model="name" type="text"
                       class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                       placeholder="Contoh: IT, HR, Finance" />
                @error('name')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Manager</label>
                <select wire:model="managerId"
                        class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">— Pilih Manager —</option>
                    @foreach($managers as $manager)
                        <option value="{{ $manager->id }}">
                            {{ $manager->full_name }} ({{ $manager->employee_id }})
                        </option>
                    @endforeach
                </select>
                @error('managerId')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <x-slot:footer>
            <button @click="$dispatch('close-modal', { name: 'dept-form' })"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                Batal
            </button>
            <x-ui.button wire:click="save">Simpan</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
