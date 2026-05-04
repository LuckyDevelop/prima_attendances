<div>
    <x-admin.page-header title="Laporan Cuti">
        <x-slot:actions>
            <button onclick="window.print()"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 print:hidden">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z" />
                </svg>
                Cetak
            </button>
        </x-slot:actions>
    </x-admin.page-header>

    {{-- Filters --}}
    <div class="mb-6 rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200 print:hidden">
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
                <label class="mb-1 block text-xs font-medium text-gray-600">Jenis Cuti</label>
                <select wire:model.live="leaveTypeId"
                    class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Semua Jenis</option>
                    @foreach ($leaveTypes as $type)
                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="mt-3 flex flex-wrap items-center gap-3">
            <div class="flex-1 min-w-48">
                <input wire:model.live.debounce.400ms="search" type="text" placeholder="Cari nama atau NIK..."
                    class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
            </div>
            <div class="w-44">
                <select wire:model.live="status"
                    class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Semua Status</option>
                    <option value="approved">Disetujui</option>
                    <option value="pending">Menunggu</option>
                    <option value="rejected">Ditolak</option>
                </select>
            </div>
            <button wire:click="resetFilters"
                class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">
                Reset Filter
            </button>
        </div>
    </div>

    {{-- Print header --}}
    <div class="hidden print:block mb-6">
        <h1 class="text-xl font-bold text-gray-900">Laporan Cuti Karyawan</h1>
        <p class="text-sm text-gray-600">
            Periode: {{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }} —
            {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}
        </p>
    </div>

    {{-- Summary cards --}}
    @if ($rows->isNotEmpty())
        <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-4 print:grid-cols-4">
            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200 text-center">
                <div class="text-2xl font-bold text-gray-900">{{ $rows->count() }}</div>
                <div class="text-xs text-gray-500 mt-1">Total Baris</div>
            </div>
            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200 text-center">
                <div class="text-2xl font-bold text-green-600">{{ $rows->sum('approved_days') }}</div>
                <div class="text-xs text-gray-500 mt-1">Hari Disetujui</div>
            </div>
            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200 text-center">
                <div class="text-2xl font-bold text-yellow-600">{{ $rows->sum('pending_days') }}</div>
                <div class="text-xs text-gray-500 mt-1">Hari Menunggu</div>
            </div>
            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200 text-center">
                <div class="text-2xl font-bold text-red-600">{{ $rows->sum('rejected_days') }}</div>
                <div class="text-xs text-gray-500 mt-1">Hari Ditolak</div>
            </div>
        </div>
    @endif

    {{-- Table --}}
    <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <th class="px-4 py-3">Karyawan</th>
                        <th class="px-4 py-3">Departemen</th>
                        <th class="px-4 py-3">Jenis Cuti</th>
                        <th class="px-4 py-3 text-center">Disetujui</th>
                        <th class="px-4 py-3 text-center">Menunggu</th>
                        <th class="px-4 py-3 text-center">Ditolak</th>
                        <th class="px-4 py-3 text-center">Total Pengajuan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($rows as $row)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900">{{ $row->full_name }}</div>
                                <div class="text-xs text-gray-500">{{ $row->employee_id }}</div>
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ $row->department_name ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $row->leave_type_name }}</td>
                            <td class="px-4 py-3 text-center">
                                @if ($row->approved_days > 0)
                                    <span class="font-semibold text-green-700">{{ $row->approved_days }} hari</span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if ($row->pending_days > 0)
                                    <span class="font-semibold text-yellow-700">{{ $row->pending_days }} hari</span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if ($row->rejected_days > 0)
                                    <span class="font-semibold text-red-700">{{ $row->rejected_days }} hari</span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center text-gray-700">{{ $row->total_requests }}x</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-gray-500">
                                Tidak ada data untuk filter yang dipilih.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if ($rows->isNotEmpty())
                    <tfoot>
                        <tr class="border-t border-gray-200 bg-gray-50 font-semibold text-gray-700">
                            <td class="px-4 py-3" colspan="3">Total</td>
                            <td class="px-4 py-3 text-center text-green-700">{{ $rows->sum('approved_days') }} hari</td>
                            <td class="px-4 py-3 text-center text-yellow-700">{{ $rows->sum('pending_days') }} hari</td>
                            <td class="px-4 py-3 text-center text-red-700">{{ $rows->sum('rejected_days') }} hari</td>
                            <td class="px-4 py-3 text-center">{{ $rows->sum('total_requests') }}x</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

    <style>
        @media print {
            body { font-size: 12px; }
            table { width: 100%; border-collapse: collapse; }
            th, td { border: 1px solid #e5e7eb; padding: 6px 8px; }
            thead { background-color: #f9fafb !important; -webkit-print-color-adjust: exact; }
        }
    </style>
</div>
