<div>
    <x-admin.page-header title="Jadwal Shift">
        <x-slot:actions>
            <button wire:click="openBulk"
                class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Jadwalkan Massal
            </button>
        </x-slot:actions>
    </x-admin.page-header>

    {{-- Controls --}}
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">

        {{-- Week Navigation --}}
        <div class="flex items-center gap-2">
            <button wire:click="previousWeek"
                class="rounded-lg border border-gray-300 bg-white p-2 text-gray-600 hover:bg-gray-50">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                </svg>
            </button>
            <span class="min-w-[200px] text-center text-sm font-semibold text-gray-800">
                {{ \Carbon\Carbon::parse($weekStart)->format('d M') }}
                –
                {{ \Carbon\Carbon::parse($weekStart)->addDays(6)->format('d M Y') }}
            </span>
            <button wire:click="nextWeek"
                class="rounded-lg border border-gray-300 bg-white p-2 text-gray-600 hover:bg-gray-50">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                </svg>
            </button>
            <button wire:click="goToCurrentWeek"
                class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-600 hover:bg-gray-50">
                Minggu Ini
            </button>
        </div>

        {{-- Department Filter --}}
        <select wire:model.live="departmentId"
            class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="">Semua Departemen</option>
            @foreach ($departments as $dept)
                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
            @endforeach
        </select>
    </div>

    {{-- Calendar Grid --}}
    <div class="overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-gray-200">
        <table class="min-w-full">
            <thead>
                <tr class="border-b border-gray-200 bg-gray-50">
                    <th
                        class="sticky left-0 z-10 bg-gray-50 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 min-w-[180px]">
                        Karyawan
                    </th>
                    @foreach ($weekDays as $day)
                        <th
                            class="px-2 py-3 text-center text-xs font-semibold uppercase tracking-wide min-w-[100px]
                        {{ $day->isToday() ? 'text-blue-600' : 'text-gray-500' }}
                        {{ $day->isWeekend() ? 'bg-gray-100' : '' }}">
                            <div>{{ $day->format('D') }}</div>
                            <div
                                class="text-sm {{ $day->isToday() ? 'rounded-full bg-blue-600 text-white mx-auto w-7 h-7 flex items-center justify-center' : 'font-normal text-gray-700' }}">
                                {{ $day->format('d') }}
                            </div>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($employees as $emp)
                    <tr wire:key="emp-{{ $emp->id }}" class="hover:bg-gray-50">
                        <td class="sticky left-0 z-10 bg-white px-4 py-3 hover:bg-gray-50">
                            <div class="flex items-center gap-2">
                                <div class="h-8 w-8 flex-shrink-0 overflow-hidden rounded-full bg-blue-100">
                                    @if ($emp->photo)
                                        <img src="{{ Storage::url($emp->photo) }}" class="h-full w-full object-cover"
                                            alt="" />
                                    @else
                                        <span
                                            class="flex h-full w-full items-center justify-center text-xs font-semibold text-blue-600">
                                            {{ strtoupper(substr($emp->full_name, 0, 2)) }}
                                        </span>
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-gray-900">{{ $emp->full_name }}</p>
                                    <p class="text-xs text-gray-500">{{ $emp->department?->name ?? '—' }}</p>
                                </div>
                            </div>
                        </td>
                        @foreach ($weekDays as $day)
                            @php
                                $dateStr = $day->toDateString();
                                $cell = $assignmentMap[$emp->id][$dateStr] ?? null;
                            @endphp
                            <td wire:key="cell-{{ $emp->id }}-{{ $dateStr }}"
                                class="px-2 py-3 text-center {{ $day->isWeekend() ? 'bg-gray-50' : '' }}">
                                <button wire:click="openAssign({{ $emp->id }}, '{{ $dateStr }}')"
                                    title="{{ $cell ? $cell['shift_name'] . ' (' . $cell['start_time'] . '–' . $cell['end_time'] . ')' : 'Klik untuk atur shift' }}"
                                    class="mx-auto flex w-full items-center justify-center rounded-md px-1 py-1 text-xs transition-colors
                                    {{ $cell
                                        ? 'bg-blue-100 text-blue-800 hover:bg-blue-200 font-medium'
                                        : 'text-gray-300 hover:bg-gray-100 hover:text-gray-500 border border-dashed border-gray-200' }}">
                                    @if ($cell)
                                        <span class="truncate max-w-[80px]">{{ $cell['shift_name'] }}</span>
                                    @else
                                        <span>—</span>
                                    @endif
                                </button>
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($weekDays) + 1 }}" class="px-4 py-12 text-center">
                            <x-admin.empty-state title="Tidak ada karyawan"
                                description="Tidak ada karyawan aktif{{ $departmentId ? ' di departemen ini' : '' }}." />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Legend --}}
    <div class="mt-3 flex items-center gap-4 text-xs text-gray-500">
        <span class="flex items-center gap-1.5">
            <span class="inline-block h-3 w-8 rounded bg-blue-100"></span> Shift terjadwal
        </span>
        <span class="flex items-center gap-1.5">
            <span class="inline-block h-3 w-8 rounded border border-dashed border-gray-200 bg-white"></span> Belum
            diatur
        </span>
        <span class="flex items-center gap-1.5">
            <span class="inline-block h-3 w-8 rounded bg-gray-50 border border-gray-200"></span> Akhir pekan
        </span>
    </div>

    {{-- Single Assignment Modal --}}
    <x-ui.modal name="assign-shift" max-width="sm">
        <x-slot:title>
            Atur Shift — {{ $assignDate ? \Carbon\Carbon::parse($assignDate)->isoFormat('dddd, D MMM Y') : '' }}
        </x-slot:title>

        <div class="space-y-3">
            <p class="text-sm text-gray-600">
                Karyawan: <span class="font-medium text-gray-900">{{ $assignUserName }}</span>
            </p>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Shift</label>
                <select wire:model="assignShiftId"
                    class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">— Hapus shift (libur) —</option>
                    @foreach ($shifts as $shift)
                        <option value="{{ $shift->id }}">
                            {{ $shift->name }}
                            ({{ substr($shift->start_time, 0, 5) }}–{{ substr($shift->end_time, 0, 5) }})
                        </option>
                    @endforeach
                </select>
                @error('assignShiftId')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            @if (!$shifts->count())
                <p class="text-xs text-yellow-600 bg-yellow-50 rounded p-2">
                    Belum ada shift. Tambahkan shift di menu <a href="{{ route('admin.master.shifts') }}"
                        class="underline">Master → Shift</a>.
                </p>
            @endif
        </div>

        <x-slot:footer>
            <button @click="$dispatch('close-modal', { name: 'assign-shift' })"
                class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                Batal
            </button>
            <button wire:click="saveAssignment" wire:loading.attr="disabled"
                class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-60">
                <span wire:loading.remove>Simpan</span>
                <span wire:loading><x-ui.spinner class="h-4 w-4" /> Menyimpan...</span>
            </button>
        </x-slot:footer>
    </x-ui.modal>

    {{-- Bulk Assignment Modal --}}
    <x-ui.modal name="bulk-assign" max-width="lg">
        <x-slot:title>Jadwalkan Massal</x-slot:title>

        <div class="space-y-4">
            {{-- Employee selection --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Karyawan <span class="text-red-500">*</span>
                </label>
                <div class="max-h-48 overflow-y-auto rounded-lg border border-gray-300 divide-y divide-gray-100">
                    @foreach ($employees as $emp)
                        <label class="flex cursor-pointer items-center gap-3 px-3 py-2 hover:bg-gray-50">
                            <input type="checkbox" wire:model="bulkUserIds" value="{{ $emp->id }}"
                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
                            <span class="text-sm text-gray-800">{{ $emp->full_name }}</span>
                            <span class="ml-auto text-xs text-gray-500">{{ $emp->department?->name ?? '—' }}</span>
                        </label>
                    @endforeach
                </div>
                @error('bulkUserIds')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-gray-500">{{ count($bulkUserIds) }} karyawan dipilih</p>
            </div>

            {{-- Date range --}}
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Dari Tanggal <span
                            class="text-red-500">*</span></label>
                    <input type="date" wire:model="bulkDateFrom"
                        class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                    @error('bulkDateFrom')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sampai Tanggal <span
                            class="text-red-500">*</span></label>
                    <input type="date" wire:model="bulkDateTo"
                        class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                    @error('bulkDateTo')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Pattern + Shift --}}
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Pola Hari</label>
                    <select wire:model="bulkPattern"
                        class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="weekdays">Hari Kerja (Sen–Jum)</option>
                        <option value="all">Semua Hari</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Shift <span
                            class="text-red-500">*</span></label>
                    <select wire:model="bulkShiftId"
                        class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Pilih shift...</option>
                        @foreach ($shifts as $shift)
                            <option value="{{ $shift->id }}">
                                {{ $shift->name }}
                                ({{ substr($shift->start_time, 0, 5) }}–{{ substr($shift->end_time, 0, 5) }})
                            </option>
                        @endforeach
                    </select>
                    @error('bulkShiftId')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Preview result --}}
            @if ($bulkPreviewReady)
                <div class="rounded-lg bg-blue-50 p-3 text-sm text-blue-800">
                    <p class="font-medium">Preview:</p>
                    <p>
                        <strong>{{ count($bulkUserIds) }}</strong> karyawan ×
                        ({{ $bulkPattern === 'weekdays' ? 'hari kerja' : 'semua hari' }} di
                        {{ \Carbon\Carbon::parse($bulkDateFrom)->format('d M') }}–{{ \Carbon\Carbon::parse($bulkDateTo)->format('d M Y') }})
                        = <strong>{{ $bulkPreviewCount }} jadwal</strong> akan disimpan.
                    </p>
                    <p class="mt-1 text-xs text-blue-600">Jadwal yang sudah ada pada tanggal yang sama akan ditimpa.
                    </p>
                </div>
            @endif
        </div>

        <x-slot:footer>
            <button @click="$dispatch('close-modal', { name: 'bulk-assign' })"
                class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                Batal
            </button>
            @if (!$bulkPreviewReady)
                <button wire:click="generateBulkPreview" wire:loading.attr="disabled"
                    class="inline-flex items-center gap-2 rounded-lg border border-blue-600 bg-white px-4 py-2 text-sm font-medium text-blue-700 hover:bg-blue-50 disabled:opacity-60">
                    <span wire:loading.remove wire:target="generateBulkPreview">Buat Preview</span>
                    <span wire:loading wire:target="generateBulkPreview"><x-ui.spinner class="h-4 w-4" />
                        Memproses...</span>
                </button>
            @else
                <button wire:click="confirmBulk" wire:loading.attr="disabled"
                    class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-60">
                    <span wire:loading.remove wire:target="confirmBulk">Konfirmasi & Simpan</span>
                    <span wire:loading wire:target="confirmBulk"><x-ui.spinner class="h-4 w-4" /> Menyimpan...</span>
                </button>
            @endif
        </x-slot:footer>
    </x-ui.modal>
</div>
