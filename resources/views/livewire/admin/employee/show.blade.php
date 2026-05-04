<div>
    <x-admin.page-header :title="$employee->full_name">
        <x-slot:actions>
            <x-ui.button variant="secondary" href="{{ route('admin.employees.index') }}" wire:navigate>
                Kembali
            </x-ui.button>
            <x-ui.button href="{{ route('admin.employees.edit', $employee->id) }}" wire:navigate>
                Edit
            </x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    {{-- Profile summary --}}
    <x-ui.card class="mb-6">
        <div class="flex items-start gap-5">
            <x-ui.avatar
                :name="$employee->full_name"
                :src="$employee->photo ? Storage::url($employee->photo) : null"
                class="h-16 w-16 flex-shrink-0 text-lg" />
            <div class="flex-1 grid grid-cols-2 gap-x-8 gap-y-2 sm:grid-cols-4">
                <div>
                    <p class="text-xs text-gray-400">NIK</p>
                    <p class="text-sm font-mono font-medium text-gray-900">{{ $employee->employee_id }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400">Email</p>
                    <p class="text-sm text-gray-900">{{ $employee->email }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400">Telepon</p>
                    <p class="text-sm text-gray-900">{{ $employee->phone ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400">Departemen</p>
                    <p class="text-sm text-gray-900">{{ $employee->department?->name ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400">Role</p>
                    <x-ui.badge :status="$employee->role->value">{{ $employee->role->label() }}</x-ui.badge>
                </div>
                <div>
                    <p class="text-xs text-gray-400">Status</p>
                    <x-ui.badge :status="$employee->status->value">{{ ucfirst($employee->status->value) }}</x-ui.badge>
                </div>
                <div>
                    <p class="text-xs text-gray-400">Lokasi Kantor</p>
                    <p class="text-sm text-gray-900">{{ $employee->officeLocation?->name ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400">Bergabung</p>
                    <p class="text-sm text-gray-900">{{ $employee->created_at->format('d M Y') }}</p>
                </div>
            </div>
        </div>
    </x-ui.card>

    {{-- Tabs --}}
    <div class="border-b border-gray-200 mb-6">
        <nav class="-mb-px flex gap-6">
            @foreach([
                'info'       => 'Info Lengkap',
                'attendance' => 'Riwayat Absensi',
                'leaves'     => 'Riwayat Cuti',
                'devices'    => 'Devices',
                'audit'      => 'Audit Log',
            ] as $tab => $label)
            <button wire:click="setTab('{{ $tab }}')"
                    @class([
                        'border-b-2 pb-3 text-sm font-medium whitespace-nowrap transition-colors',
                        'border-blue-600 text-blue-600' => $activeTab === $tab,
                        'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' => $activeTab !== $tab,
                    ])>
                {{ $label }}
            </button>
            @endforeach
        </nav>
    </div>

    {{-- Tab: Info --}}
    @if($activeTab === 'info')
    <x-ui.card>
        <dl class="grid grid-cols-1 gap-x-8 gap-y-4 sm:grid-cols-2">
            <div>
                <dt class="text-xs font-medium text-gray-400 uppercase tracking-wider">Nama Lengkap</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $employee->full_name }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-gray-400 uppercase tracking-wider">NIK</dt>
                <dd class="mt-1 text-sm font-mono text-gray-900">{{ $employee->employee_id }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-gray-400 uppercase tracking-wider">Email</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $employee->email }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-gray-400 uppercase tracking-wider">Telepon</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $employee->phone ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-gray-400 uppercase tracking-wider">Departemen</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $employee->department?->name ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-gray-400 uppercase tracking-wider">Lokasi Kantor</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $employee->officeLocation?->name ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-gray-400 uppercase tracking-wider">Role</dt>
                <dd class="mt-1"><x-ui.badge :status="$employee->role->value">{{ $employee->role->label() }}</x-ui.badge></dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-gray-400 uppercase tracking-wider">Status</dt>
                <dd class="mt-1"><x-ui.badge :status="$employee->status->value">{{ ucfirst($employee->status->value) }}</x-ui.badge></dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-gray-400 uppercase tracking-wider">Verifikasi Wajah</dt>
                <dd class="mt-1 text-sm text-gray-900">
                    {{ $employee->has_face_embedding ? 'Terdaftar' : 'Belum terdaftar' }}
                </dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-gray-400 uppercase tracking-wider">Email Terverifikasi</dt>
                <dd class="mt-1 text-sm text-gray-900">
                    {{ $employee->email_verified_at ? $employee->email_verified_at->format('d M Y') : 'Belum' }}
                </dd>
            </div>
        </dl>
    </x-ui.card>
    @endif

    {{-- Tab: Attendance --}}
    @if($activeTab === 'attendance')
    <x-admin.data-table :headers="['Tanggal', 'Masuk', 'Keluar', 'Durasi', 'Status', 'Mock GPS']">
        @forelse($attendances as $att)
        <tr wire:key="att-{{ $att->id }}">
            <td class="px-6 py-4 text-sm text-gray-900">
                {{ \Carbon\Carbon::parse($att->work_date)->format('d M Y') }}
            </td>
            <td class="px-6 py-4 text-sm text-gray-600">
                {{ $att->check_in_time ? \Carbon\Carbon::parse($att->check_in_time)->format('H:i') : '—' }}
            </td>
            <td class="px-6 py-4 text-sm text-gray-600">
                {{ $att->check_out_time ? \Carbon\Carbon::parse($att->check_out_time)->format('H:i') : '—' }}
            </td>
            <td class="px-6 py-4 text-sm text-gray-600">
                @if($att->work_duration_min > 0)
                    {{ floor($att->work_duration_min / 60) }}j {{ $att->work_duration_min % 60 }}m
                @else
                    —
                @endif
            </td>
            <td class="px-6 py-4">
                <x-ui.badge :status="$att->status->value">{{ ucfirst($att->status->value) }}</x-ui.badge>
            </td>
            <td class="px-6 py-4 text-sm">
                @if($att->is_mock_location)
                    <span class="text-red-600 font-medium">Ya</span>
                @else
                    <span class="text-gray-400">Tidak</span>
                @endif
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="6" class="px-6 py-12">
                <x-admin.empty-state title="Belum ada data absensi" description="Karyawan ini belum memiliki riwayat absensi." />
            </td>
        </tr>
        @endforelse

        <x-slot:footer>
            {{ $attendances->links() }}
        </x-slot:footer>
    </x-admin.data-table>
    @endif

    {{-- Tab: Leaves --}}
    @if($activeTab === 'leaves')
    <x-admin.data-table :headers="['Jenis', 'Mulai', 'Selesai', 'Hari', 'Status', 'Diajukan']">
        @forelse($leaves as $leave)
        <tr wire:key="leave-{{ $leave->id }}">
            <td class="px-6 py-4 text-sm text-gray-900">{{ $leave->leaveType->name }}</td>
            <td class="px-6 py-4 text-sm text-gray-600">{{ \Carbon\Carbon::parse($leave->start_date)->format('d M Y') }}</td>
            <td class="px-6 py-4 text-sm text-gray-600">{{ \Carbon\Carbon::parse($leave->end_date)->format('d M Y') }}</td>
            <td class="px-6 py-4 text-sm text-gray-600">{{ $leave->total_days }}</td>
            <td class="px-6 py-4">
                <x-ui.badge :status="$leave->status->value">{{ ucfirst($leave->status->value) }}</x-ui.badge>
            </td>
            <td class="px-6 py-4 text-sm text-gray-500">
                {{ $leave->submitted_at ? \Carbon\Carbon::parse($leave->submitted_at)->format('d M Y') : '—' }}
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="6" class="px-6 py-12">
                <x-admin.empty-state title="Belum ada pengajuan cuti" description="Karyawan ini belum mengajukan cuti." />
            </td>
        </tr>
        @endforelse

        <x-slot:footer>
            {{ $leaves->links() }}
        </x-slot:footer>
    </x-admin.data-table>
    @endif

    {{-- Tab: Devices --}}
    @if($activeTab === 'devices')
    <x-admin.data-table :headers="['Nama Device', 'Platform', 'Status', 'Login Terakhir', 'Aksi']">
        @forelse($devices as $device)
        <tr wire:key="device-{{ $device->id }}">
            <td class="px-6 py-4">
                <p class="text-sm font-medium text-gray-900">{{ $device->device_name ?? '—' }}</p>
                <p class="text-xs text-gray-400 font-mono truncate max-w-[200px]">{{ $device->device_id ?? '—' }}</p>
            </td>
            <td class="px-6 py-4 text-sm text-gray-600 capitalize">{{ $device->platform ?? '—' }}</td>
            <td class="px-6 py-4">
                <x-ui.badge :status="$device->is_active ? 'active' : 'inactive'">
                    {{ $device->is_active ? 'Aktif' : 'Nonaktif' }}
                </x-ui.badge>
            </td>
            <td class="px-6 py-4 text-sm text-gray-500">
                {{ $device->last_login ? \Carbon\Carbon::parse($device->last_login)->diffForHumans() : '—' }}
            </td>
            <td class="px-6 py-4">
                @if($device->is_active)
                <button wire:click="revokeDevice({{ $device->id }})"
                        wire:confirm="Nonaktifkan device ini?"
                        class="text-xs text-red-600 hover:underline">
                    Revoke
                </button>
                @endif
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="5" class="px-6 py-12">
                <x-admin.empty-state title="Tidak ada device" description="Karyawan belum pernah login dari device manapun." />
            </td>
        </tr>
        @endforelse
    </x-admin.data-table>
    @endif

    {{-- Tab: Audit --}}
    @if($activeTab === 'audit')
    <x-admin.data-table :headers="['Waktu', 'Aksi', 'Entitas', 'IP']">
        @forelse($auditLogs as $log)
        <tr wire:key="audit-{{ $log->id }}">
            <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">
                {{ $log->created_at->format('d M Y H:i') }}
            </td>
            <td class="px-6 py-4 text-sm text-gray-900">{{ $log->action }}</td>
            <td class="px-6 py-4 text-sm text-gray-600">
                {{ $log->entity ?? '—' }}
                @if($log->entity_id) <span class="text-xs text-gray-400">#{{ $log->entity_id }}</span> @endif
            </td>
            <td class="px-6 py-4 text-sm font-mono text-gray-400">{{ $log->ip_address ?? '—' }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="4" class="px-6 py-12">
                <x-admin.empty-state title="Tidak ada log" description="Belum ada aktivitas yang tercatat untuk karyawan ini." />
            </td>
        </tr>
        @endforelse

        <x-slot:footer>
            {{ $auditLogs->links() }}
        </x-slot:footer>
    </x-admin.data-table>
    @endif
</div>
