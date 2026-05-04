<div>
    <x-admin.page-header title="Kalender Cuti" />

    {{-- Filters & Navigation --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <button wire:click="prevMonth"
                    class="rounded-lg border border-gray-300 bg-white p-2 text-gray-600 hover:bg-gray-50">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                </svg>
            </button>
            <h2 class="text-lg font-semibold text-gray-900">
                {{ $firstDay->isoFormat('MMMM YYYY') }}
            </h2>
            <button wire:click="nextMonth"
                    class="rounded-lg border border-gray-300 bg-white p-2 text-gray-600 hover:bg-gray-50">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                </svg>
            </button>
        </div>
        <div>
            <select wire:model.live="departmentId"
                    class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">Semua Departemen</option>
                @foreach($departments as $dept)
                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Calendar Grid --}}
    <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 overflow-hidden">
        {{-- Day headers --}}
        <div class="grid grid-cols-7 border-b border-gray-200 bg-gray-50">
            @foreach(['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $day)
                <div class="px-2 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500">
                    {{ $day }}
                </div>
            @endforeach
        </div>

        {{-- Calendar cells --}}
        @php
            $cells = [];
            // Leading empty cells
            for ($i = 0; $i < $startDow; $i++) {
                $cells[] = null;
            }
            for ($d = 1; $d <= $daysInMonth; $d++) {
                $cells[] = $d;
            }
            // Trailing empty cells to fill last week
            while (count($cells) % 7 !== 0) {
                $cells[] = null;
            }
            $weeks = array_chunk($cells, 7);

            // Index events by day
            $eventsByDay = [];
            foreach ($events as $event) {
                $start = \Carbon\Carbon::parse($event['start_date']);
                $end   = \Carbon\Carbon::parse($event['end_date']);
                $cur   = $start->copy();
                while ($cur <= $end) {
                    if ($cur->year === $year && $cur->month === $month) {
                        $eventsByDay[$cur->day][] = $event;
                    }
                    $cur->addDay();
                }
            }

            $today = now()->day;
            $isCurrentMonth = now()->year === $year && now()->month === $month;
        @endphp

        @foreach($weeks as $week)
        <div class="grid grid-cols-7 border-b border-gray-100 last:border-0">
            @foreach($week as $day)
            <div class="min-h-[100px] border-r border-gray-100 last:border-0 p-2">
                @if($day)
                    <span class="mb-1 inline-flex h-6 w-6 items-center justify-center rounded-full text-xs font-medium
                        {{ $isCurrentMonth && $day === $today ? 'bg-blue-600 text-white' : 'text-gray-700' }}">
                        {{ $day }}
                    </span>
                    @if(!empty($eventsByDay[$day]))
                        <div class="space-y-0.5">
                            @foreach(array_slice($eventsByDay[$day], 0, 3) as $evt)
                                <a href="{{ route('admin.leaves.show', $evt['id']) }}"
                                   title="{{ $evt['name'] }} ({{ $evt['department'] }})"
                                   class="block truncate rounded bg-blue-100 px-1.5 py-0.5 text-xs text-blue-800 hover:bg-blue-200">
                                    {{ $evt['name'] }}
                                </a>
                            @endforeach
                            @if(count($eventsByDay[$day]) > 3)
                                <span class="block text-xs text-gray-500">+{{ count($eventsByDay[$day]) - 3 }} lainnya</span>
                            @endif
                        </div>
                    @endif
                @endif
            </div>
            @endforeach
        </div>
        @endforeach
    </div>
</div>
