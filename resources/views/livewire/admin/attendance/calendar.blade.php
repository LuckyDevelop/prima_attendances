<div>
    <x-admin.page-header title="Kalender Absensi" />

    {{-- Navigation & Filters --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">

        {{-- Month navigation --}}
        <div class="flex items-center gap-2">
            <button wire:click="prevMonth"
                    class="rounded-lg border border-gray-300 bg-white p-2 text-gray-600 hover:bg-gray-50 transition-colors">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                </svg>
            </button>
            <h2 class="min-w-[160px] text-center text-lg font-semibold text-gray-900">
                {{ $firstDay->isoFormat('MMMM YYYY') }}
            </h2>
            <button wire:click="nextMonth"
                    class="rounded-lg border border-gray-300 bg-white p-2 text-gray-600 hover:bg-gray-50 transition-colors">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                </svg>
            </button>
            @unless($firstDay->year === now()->year && $firstDay->month === now()->month)
                <button wire:click="goToToday"
                        class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-600 hover:bg-gray-50">
                    Hari Ini
                </button>
            @endunless
        </div>

        {{-- Department filter --}}
        <div class="flex items-center gap-3">
            <select wire:model.live="departmentId"
                    class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">Semua Departemen</option>
                @foreach($departments as $dept)
                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Legend --}}
    <div class="mb-4 flex flex-wrap items-center gap-4 text-xs text-gray-500">
        <span class="flex items-center gap-1.5">
            <span class="inline-block h-2.5 w-2.5 rounded-full bg-green-500"></span> Hadir
        </span>
        <span class="flex items-center gap-1.5">
            <span class="inline-block h-2.5 w-2.5 rounded-full bg-red-500"></span> Tidak Hadir
        </span>
        <span class="flex items-center gap-1.5">
            <span class="inline-block h-2.5 w-2.5 rounded-full bg-blue-500"></span> Cuti
        </span>
    </div>

    {{-- Calendar Grid --}}
    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-200">

        {{-- Day-of-week headers --}}
        <div class="grid grid-cols-7 border-b border-gray-200 bg-gray-50">
            @foreach(['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $i => $dayName)
                <div class="px-2 py-3 text-center text-xs font-semibold uppercase tracking-wide
                            {{ $i >= 5 ? 'text-gray-400' : 'text-gray-500' }}">
                    {{ $dayName }}
                </div>
            @endforeach
        </div>

        {{-- Weeks --}}
        @foreach($weeks as $week)
        <div class="grid grid-cols-7 divide-x divide-gray-100 border-b border-gray-100 last:border-0">
            @foreach($week as $colIdx => $day)
            @php
                $isWeekend = $colIdx >= 5;
                $dateStr   = $day ? $firstDay->copy()->setDay($day)->format('Y-m-d') : null;
                $summary   = $dateStr ? ($summaryMap[$dateStr] ?? null) : null;
                $isToday   = $dateStr && $dateStr === now()->toDateString();
            @endphp
            <div class="min-h-[110px] p-2 transition-colors
                        {{ $isWeekend ? 'bg-gray-50' : '' }}
                        {{ $day && $summary ? 'cursor-pointer hover:bg-blue-50' : '' }}"
                 @if($day && $summary)
                    onclick="window.location='{{ route('admin.attendances.index', array_filter(['dateFrom' => $dateStr, 'dateTo' => $dateStr, 'departmentId' => $departmentId ?: null])) }}'"
                 @endif>
                @if($day)
                    {{-- Date number --}}
                    <div class="mb-2">
                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-full text-xs font-medium
                                     {{ $isToday ? 'bg-blue-600 text-white' : ($isWeekend ? 'text-gray-400' : 'text-gray-700') }}">
                            {{ $day }}
                        </span>
                    </div>

                    @if($summary)
                        {{-- Attendance summary pills --}}
                        <div class="space-y-1">
                            @if($summary['present'] > 0)
                                <div class="flex items-center gap-1">
                                    <span class="h-2 w-2 flex-shrink-0 rounded-full bg-green-500"></span>
                                    <span class="text-xs text-green-700 font-medium">{{ $summary['present'] }} hadir</span>
                                </div>
                            @endif
                            @if($summary['absent'] > 0)
                                <div class="flex items-center gap-1">
                                    <span class="h-2 w-2 flex-shrink-0 rounded-full bg-red-500"></span>
                                    <span class="text-xs text-red-700 font-medium">{{ $summary['absent'] }} absen</span>
                                </div>
                            @endif
                            @if($summary['leave'] > 0)
                                <div class="flex items-center gap-1">
                                    <span class="h-2 w-2 flex-shrink-0 rounded-full bg-blue-500"></span>
                                    <span class="text-xs text-blue-700 font-medium">{{ $summary['leave'] }} cuti</span>
                                </div>
                            @endif
                        </div>

                        {{-- Total bar --}}
                        @php
                            $total    = $summary['total'];
                            $pPct     = $total ? round($summary['present'] / $total * 100) : 0;
                        @endphp
                        <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-gray-100">
                            <div class="h-full rounded-full bg-green-400 transition-all"
                                 style="width: {{ $pPct }}%"></div>
                        </div>
                    @else
                        {{-- No data for this day --}}
                        @if(!$isWeekend)
                            <p class="text-xs text-gray-300">—</p>
                        @endif
                    @endif
                @endif
            </div>
            @endforeach
        </div>
        @endforeach
    </div>

    {{-- Monthly totals --}}
    @php
        $monthTotal   = collect($summaryMap)->reduce(fn($c, $v) => $c + $v['total'],   0);
        $monthPresent = collect($summaryMap)->reduce(fn($c, $v) => $c + $v['present'], 0);
        $monthAbsent  = collect($summaryMap)->reduce(fn($c, $v) => $c + $v['absent'],  0);
        $monthLeave   = collect($summaryMap)->reduce(fn($c, $v) => $c + $v['leave'],   0);
    @endphp
    @if($monthTotal > 0)
    <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200">
            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Total Catatan</p>
            <p class="mt-1 text-2xl font-bold text-gray-900">{{ $monthTotal }}</p>
        </div>
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-green-200">
            <p class="text-xs font-medium uppercase tracking-wide text-green-600">Hadir</p>
            <p class="mt-1 text-2xl font-bold text-green-700">{{ $monthPresent }}</p>
        </div>
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-red-200">
            <p class="text-xs font-medium uppercase tracking-wide text-red-500">Tidak Hadir</p>
            <p class="mt-1 text-2xl font-bold text-red-600">{{ $monthAbsent }}</p>
        </div>
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-blue-200">
            <p class="text-xs font-medium uppercase tracking-wide text-blue-500">Cuti</p>
            <p class="mt-1 text-2xl font-bold text-blue-600">{{ $monthLeave }}</p>
        </div>
    </div>
    @endif
</div>
