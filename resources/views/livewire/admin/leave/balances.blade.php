<div>
    <x-admin.page-header title="Saldo Cuti {{ $year }}">
        <x-slot:actions>
            <div class="flex items-center gap-2">
                <button wire:click="$set('year', {{ $year - 1 }})"
                        class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">
                    &larr; {{ $year - 1 }}
                </button>
                <span class="text-sm font-semibold text-gray-900">{{ $year }}</span>
                <button wire:click="$set('year', {{ $year + 1 }})"
                        class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">
                    {{ $year + 1 }} &rarr;
                </button>
            </div>
        </x-slot:actions>
    </x-admin.page-header>

    {{-- Filters --}}
    <div class="mb-6 flex flex-wrap gap-3">
        <input wire:model.live.debounce.400ms="search" type="text"
               placeholder="Cari karyawan..."
               class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
        <select wire:model.live="departmentId"
                class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="">Semua Departemen</option>
            @foreach($departments as $dept)
                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
            @endforeach
        </select>
    </div>

    {{-- Table --}}
    <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <th class="px-4 py-3">Karyawan</th>
                    <th class="px-4 py-3">Departemen</th>
                    @foreach($leaveTypes as $lt)
                        <th class="px-4 py-3 text-center">{{ $lt->name }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($employees as $emp)
                <tr wire:key="emp-{{ $emp->id }}" class="hover:bg-gray-50">
                    <td class="px-4 py-3">
                        <p class="font-medium text-gray-900">{{ $emp->full_name }}</p>
                        <p class="text-xs text-gray-500">{{ $emp->employee_id }}</p>
                    </td>
                    <td class="px-4 py-3 text-gray-600">{{ $emp->department?->name ?? '—' }}</td>
                    @foreach($leaveTypes as $lt)
                        @php
                            $bal = $emp->leaveBalances->firstWhere('leave_type_id', $lt->id);
                        @endphp
                        <td class="px-4 py-3 text-center">
                            @if($bal)
                                <div class="flex items-center justify-center gap-1">
                                    <span class="font-medium text-gray-900">{{ $bal->remaining }}</span>
                                    <span class="text-gray-400">/</span>
                                    <span class="text-gray-600">{{ $bal->total_quota }}</span>
                                    <button wire:click="openEdit({{ $bal->id }})"
                                            title="Edit kuota"
                                            class="ml-1 text-gray-400 hover:text-blue-600">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" />
                                        </svg>
                                    </button>
                                </div>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                    @endforeach
                </tr>
                @empty
                <tr>
                    <td colspan="{{ 2 + count($leaveTypes) }}" class="px-4 py-12">
                        <x-admin.empty-state title="Belum ada data saldo cuti"
                            description="Data saldo akan tersedia setelah karyawan menggunakan cuti." />
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        @if($employees->hasPages())
            <div class="border-t border-gray-200 px-4 py-3">
                {{ $employees->links() }}
            </div>
        @endif
    </div>

    {{-- Edit Balance Modal --}}
    <x-ui.modal name="balance-edit" max-width="sm">
        <x-slot:title>Edit Kuota Cuti</x-slot:title>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                Total Kuota (hari) <span class="text-red-500">*</span>
            </label>
            <input wire:model="editQuota" type="number" min="0" max="365"
                   class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
            @error('editQuota') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <x-slot:footer>
            <button @click="$dispatch('close-modal', { name: 'balance-edit' })"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                Batal
            </button>
            <x-ui.button wire:click="saveBalance">Simpan</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
