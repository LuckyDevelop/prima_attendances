<div>
    <x-admin.page-header title="Daftar Absensi">
        <x-slot:actions>
            <a href="{{ route('admin.attendances.manual-entry') }}"
                class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Manual Entry
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    {{-- Filters --}}
    <div class="mb-6 rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Tanggal Dari</label>
                <input wire:model.live="dateFrom" type="date"
                    class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Tanggal Sampai</label>
                <input wire:model.live="dateTo" type="date"
                    class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Departemen</label>
                <select wire:model.live="departmentId"
                    class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Semua Departemen</option>
                    @foreach ($departments as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Status</label>
                <select wire:model.live="status"
                    class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Semua Status</option>
                    @foreach ($statuses as $s)
                        <option value="{{ $s->value }}">{{ ucfirst($s->value) }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="mt-3 flex flex-wrap items-center gap-4">
            <div class="flex-1">
                <input wire:model.live.debounce.400ms="search" type="text" placeholder="Cari nama atau NIK..."
                    class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
            </div>
            <label class="flex cursor-pointer items-center gap-2 text-sm text-gray-700">
                <input wire:model.live="lateOnly" type="checkbox"
                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
                Hanya Terlambat
            </label>
            <label class="flex cursor-pointer items-center gap-2 text-sm text-gray-700">
                <input wire:model.live="mockOnly" type="checkbox"
                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
                Hanya Mock GPS
            </label>
            <button wire:click="resetFilters"
                class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">
                Reset
            </button>
        </div>
    </div>

    {{-- Table --}}
    <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr
                        class="border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <th class="px-4 py-3">Tanggal</th>
                        <th class="px-4 py-3">Karyawan</th>
                        <th class="px-4 py-3">Departemen</th>
                        <th class="px-4 py-3">Check-In</th>
                        <th class="px-4 py-3">Check-Out</th>
                        <th class="px-4 py-3">Durasi</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Flags</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($attendances as $att)
                        <tr wire:key="att-{{ $att->id }}" class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-gray-700">
                                {{ \Carbon\Carbon::parse($att->work_date)->format('d M Y') }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="h-8 w-8 flex-shrink-0 overflow-hidden rounded-full bg-gray-200">
                                        @if ($att->user?->photo)
                                            <img src="{{ Storage::url($att->user->photo) }}"
                                                class="h-full w-full object-cover" alt="" />
                                        @else
                                            <span
                                                class="flex h-full w-full items-center justify-center text-xs font-semibold text-gray-500">
                                                {{ strtoupper(substr($att->user?->full_name ?? '?', 0, 1)) }}
                                            </span>
                                        @endif
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">{{ $att->user?->full_name ?? '—' }}</p>
                                        <p class="text-xs text-gray-500">{{ $att->user?->employee_id }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                {{ $att->user?->department?->name ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                @if ($att->check_in_time)
                                    <span class="font-medium text-gray-900">
                                        {{ \Carbon\Carbon::parse($att->check_in_time)->format('H:i') }}
                                    </span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if ($att->check_out_time)
                                    <span class="font-medium text-gray-900">
                                        {{ \Carbon\Carbon::parse($att->check_out_time)->format('H:i') }}
                                    </span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                @if ($att->work_duration_min > 0)
                                    {{ floor($att->work_duration_min / 60) }}j {{ $att->work_duration_min % 60 }}m
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $statusColors = [
                                        'present' => 'bg-green-100 text-green-700',
                                        'absent' => 'bg-red-100 text-red-700',
                                        'leave' => 'bg-blue-100 text-blue-700',
                                    ];
                                    $statusLabels = [
                                        'present' => 'Hadir',
                                        'absent' => 'Absen',
                                        'leave' => 'Cuti',
                                    ];
                                    $statusVal = is_object($att->status) ? $att->status->value : $att->status;
                                @endphp
                                <span
                                    class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $statusColors[$statusVal] ?? 'bg-gray-100 text-gray-600' }}">
                                    {{ $statusLabels[$statusVal] ?? ucfirst($statusVal) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-1">
                                    @if ($att->is_mock_location)
                                        <span title="Mock Location"
                                            class="inline-flex items-center rounded-full bg-red-100 px-1.5 py-0.5 text-xs font-medium text-red-700">GPS!</span>
                                    @endif
                                    @if ($att->face_verified)
                                        <span title="Face Verified"
                                            class="inline-flex items-center rounded-full bg-green-100 px-1.5 py-0.5 text-xs font-medium text-green-700">Wajah
                                            ✓</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.attendances.show', $att->id) }}"
                                    class="text-sm text-blue-600 hover:underline">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-12">
                                <x-admin.empty-state title="Belum ada data absensi"
                                    description="Tidak ada absensi yang sesuai dengan filter yang dipilih." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($attendances->hasPages())
            <div class="border-t border-gray-200 px-4 py-3">
                {{ $attendances->links() }}
            </div>
        @endif
    </div>
</div>
