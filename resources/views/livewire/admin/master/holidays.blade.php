<div>
    <x-admin.page-header title="Hari Libur">
        <x-slot:actions>
            <x-ui.button wire:click="openCreate">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Tambah Hari Libur
            </x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    {{-- Year navigation --}}
    <x-ui.card class="mb-5">
        <div class="flex items-center justify-between">
            <button wire:click="prevYear"
                    class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700 transition-colors">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                </svg>
            </button>
            <span class="text-lg font-semibold text-gray-900">{{ $year }}</span>
            <button wire:click="nextYear"
                    class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700 transition-colors">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                </svg>
            </button>
        </div>
    </x-ui.card>

    <x-admin.data-table :headers="['Tanggal', 'Nama Hari Libur', 'Tipe', 'Aksi']">
        @forelse($holidays as $holiday)
        <tr wire:key="hol-{{ $holiday->id }}">
            <td class="px-6 py-4 text-sm font-medium text-gray-900 whitespace-nowrap">
                {{ \Carbon\Carbon::parse($holiday->date)->locale('id')->isoFormat('dddd, D MMMM Y') }}
            </td>
            <td class="px-6 py-4 text-sm text-gray-700">{{ $holiday->name }}</td>
            <td class="px-6 py-4">
                @if($holiday->is_national)
                    <x-ui.badge status="info">Nasional</x-ui.badge>
                @else
                    <x-ui.badge status="default">Perusahaan</x-ui.badge>
                @endif
            </td>
            <td class="px-6 py-4 text-right">
                <div class="flex items-center justify-end gap-3">
                    <button wire:click="openEdit({{ $holiday->id }})"
                            class="text-sm text-blue-600 hover:underline">Edit</button>
                    <button wire:click="delete({{ $holiday->id }})"
                            wire:confirm="Hapus hari libur '{{ $holiday->name }}'?"
                            class="text-sm text-red-600 hover:underline">Hapus</button>
                </div>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="4" class="px-6 py-12">
                <x-admin.empty-state
                    title="Tidak ada hari libur di {{ $year }}"
                    description="Tambahkan hari libur nasional atau perusahaan." />
            </td>
        </tr>
        @endforelse
    </x-admin.data-table>

    {{-- Modal --}}
    <x-ui.modal name="holiday-form" max-width="md">
        <x-slot:title>{{ $editingId ? 'Edit Hari Libur' : 'Tambah Hari Libur' }}</x-slot:title>

        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Tanggal <span class="text-red-500">*</span>
                </label>
                <input wire:model="date" type="date"
                       class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                @error('date')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Nama Hari Libur <span class="text-red-500">*</span>
                </label>
                <input wire:model="name" type="text"
                       class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                       placeholder="Contoh: Hari Raya Idul Fitri" />
                @error('name')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <label class="flex items-center gap-3 cursor-pointer">
                <input wire:model="isNational" type="checkbox"
                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
                <span class="text-sm text-gray-700">Hari libur nasional</span>
            </label>
        </div>

        <x-slot:footer>
            <button @click="$dispatch('close-modal', { name: 'holiday-form' })"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                Batal
            </button>
            <x-ui.button wire:click="save">Simpan</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
