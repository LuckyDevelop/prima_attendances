<div>
    <x-admin.page-header title="Daftar Izin" />

    {{-- Filters --}}
    <div class="mb-6 rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Status</label>
                <select wire:model.live="status"
                        class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Semua Status</option>
                    @foreach($statuses as $s)
                        <option value="{{ $s->value }}">{{ ucfirst($s->value) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Jenis Izin</label>
                <select wire:model.live="permissionType"
                        class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Semua Jenis</option>
                    @foreach($permissionTypes as $pt)
                        <option value="{{ $pt->value }}">{{ ucwords(str_replace('_', ' ', $pt->value)) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Departemen</label>
                <select wire:model.live="departmentId"
                        class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Semua Departemen</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Cari</label>
                <input wire:model.live.debounce.400ms="search" type="text"
                       placeholder="Nama atau NIK..."
                       class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
            </div>
        </div>
        <div class="mt-3 flex flex-wrap items-center gap-3">
            <div class="flex gap-3">
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">Tanggal Dari</label>
                    <input wire:model.live="dateFrom" type="date"
                           class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">Tanggal Sampai</label>
                    <input wire:model.live="dateTo" type="date"
                           class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                </div>
            </div>
            <div class="flex items-end pb-0">
                <button wire:click="resetFilters"
                        class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">
                    Reset
                </button>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <th class="px-4 py-3">Tgl Pengajuan</th>
                        <th class="px-4 py-3">Karyawan</th>
                        <th class="px-4 py-3">Departemen</th>
                        <th class="px-4 py-3">Jenis Izin</th>
                        <th class="px-4 py-3">Tgl Izin</th>
                        <th class="px-4 py-3">Waktu</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($permissions as $perm)
                    <tr wire:key="perm-{{ $perm->id }}" class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-gray-600">
                            {{ $perm->submitted_at ? \Carbon\Carbon::parse($perm->submitted_at)->format('d M Y') : '—' }}
                        </td>
                        <td class="px-4 py-3">
                            <p class="font-medium text-gray-900">{{ $perm->user?->full_name ?? '—' }}</p>
                            <p class="text-xs text-gray-500">{{ $perm->user?->employee_id }}</p>
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $perm->user?->department?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-700">
                            {{ ucwords(str_replace('_', ' ', is_object($perm->permission_type) ? $perm->permission_type->value : $perm->permission_type)) }}
                        </td>
                        <td class="px-4 py-3 text-gray-700">
                            {{ \Carbon\Carbon::parse($perm->request_date)->format('d M Y') }}
                        </td>
                        <td class="px-4 py-3 text-gray-600">
                            @if($perm->start_time && $perm->end_time)
                                {{ substr($perm->start_time, 0, 5) }} – {{ substr($perm->end_time, 0, 5) }}
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @php
                                $colors = ['pending' => 'bg-yellow-100 text-yellow-700', 'approved' => 'bg-green-100 text-green-700', 'rejected' => 'bg-red-100 text-red-700'];
                                $labels = ['pending' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'];
                                $sv = is_object($perm->status) ? $perm->status->value : $perm->status;
                            @endphp
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $colors[$sv] ?? 'bg-gray-100 text-gray-600' }}">
                                {{ $labels[$sv] ?? ucfirst($sv) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.permissions.show', $perm->id) }}"
                               class="text-sm text-blue-600 hover:underline">Detail</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-12">
                            <x-admin.empty-state title="Belum ada pengajuan izin"
                                description="Tidak ada data izin yang sesuai dengan filter." />
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($permissions->hasPages())
            <div class="border-t border-gray-200 px-4 py-3">
                {{ $permissions->links() }}
            </div>
        @endif
    </div>
</div>
