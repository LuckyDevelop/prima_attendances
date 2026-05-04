<div>
    <x-admin.page-header title="Dashboard">
        <x-slot:actions>
            <span class="text-sm text-gray-500">
                {{ now()->locale('id')->isoFormat('dddd, D MMMM YYYY') }}
            </span>
        </x-slot:actions>
    </x-admin.page-header>

    {{-- Stat Cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">

        <x-admin.stat-card label="Total Karyawan" :value="$totalEmployees" color="blue">
            <x-slot:icon>
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                </svg>
            </x-slot:icon>
        </x-admin.stat-card>

        <x-admin.stat-card label="Hadir Hari Ini" :value="$presentToday" color="green">
            <x-slot:icon>
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </x-slot:icon>
        </x-admin.stat-card>

        <x-admin.stat-card label="Terlambat" :value="$lateToday" color="yellow">
            <x-slot:icon>
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </x-slot:icon>
        </x-admin.stat-card>

        <x-admin.stat-card label="Cuti Hari Ini" :value="$onLeaveToday" color="purple">
            <x-slot:icon>
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                </svg>
            </x-slot:icon>
        </x-admin.stat-card>
    </div>

    {{-- Main grid --}}
    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- Pending Approvals --}}
        <div class="lg:col-span-2">
            <x-ui.card>
                <x-slot:header>
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-900">Pending Approvals</h3>
                        <a href="{{ route('admin.approvals.index') }}"
                           class="text-xs text-blue-600 hover:underline">Lihat semua</a>
                    </div>
                </x-slot:header>

                @if($pendingLeaves->isEmpty())
                    <x-admin.empty-state
                        title="Tidak ada pengajuan"
                        description="Semua pengajuan sudah diproses." />
                @else
                    <ul class="divide-y divide-gray-100">
                        @foreach($pendingLeaves as $leave)
                        <li class="flex items-center justify-between py-3">
                            <div class="flex items-center gap-3">
                                <x-ui.avatar
                                    :name="$leave->user->full_name"
                                    :src="$leave->user->photo ? Storage::url($leave->user->photo) : null"
                                    class="h-8 w-8 flex-shrink-0" />
                                <div>
                                    <p class="text-sm font-medium text-gray-900">
                                        {{ $leave->user->full_name }}
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        {{ $leave->leaveType->name }} ·
                                        {{ $leave->total_days }} hari
                                        ({{ \Carbon\Carbon::parse($leave->start_date)->format('d M') }}–{{ \Carbon\Carbon::parse($leave->end_date)->format('d M Y') }})
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <x-ui.badge status="pending" />
                                <span class="text-xs text-gray-400">
                                    {{ $leave->submitted_at?->diffForHumans() ?? '—' }}
                                </span>
                            </div>
                        </li>
                        @endforeach
                    </ul>
                @endif
            </x-ui.card>
        </div>

        {{-- Recent Activity --}}
        <div>
            <x-ui.card>
                <x-slot:header>
                    <h3 class="text-sm font-semibold text-gray-900">Aktivitas Terbaru</h3>
                </x-slot:header>

                @if($recentActivity->isEmpty())
                    <x-admin.empty-state title="Belum ada aktivitas" />
                @else
                    <ul class="space-y-3">
                        @foreach($recentActivity as $log)
                        <li class="flex items-start gap-3">
                            <x-ui.avatar
                                :name="$log->user?->full_name ?? 'System'"
                                class="h-7 w-7 flex-shrink-0 mt-0.5" />
                            <div class="min-w-0 flex-1">
                                <p class="text-xs text-gray-700 leading-snug">
                                    <span class="font-medium">{{ $log->user?->full_name ?? 'System' }}</span>
                                    · {{ $log->action }}
                                    @if($log->entity) <span class="text-gray-400">{{ $log->entity }}</span> @endif
                                </p>
                                <p class="text-xs text-gray-400 mt-0.5">
                                    {{ $log->created_at->diffForHumans() }}
                                </p>
                            </div>
                        </li>
                        @endforeach
                    </ul>
                @endif
            </x-ui.card>
        </div>
    </div>
</div>
