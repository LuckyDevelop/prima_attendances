@php
    use App\Enums\UserRole;
    $user = auth()->user();
    $isHrOrAdmin = in_array($user->role, [UserRole::HR, UserRole::ADMIN]);
    $isAdmin = $user->role === UserRole::ADMIN;
    $isManagerPlus = in_array($user->role, [UserRole::MANAGER, UserRole::HR, UserRole::ADMIN]);
@endphp

<aside
    class="flex flex-shrink-0 flex-col overflow-x-hidden overflow-y-auto bg-gray-900 text-white transition-all duration-300 ease-in-out fixed inset-y-0 left-0 z-30 lg:relative lg:z-auto lg:translate-x-0"
    :class="{
        'translate-x-0': mobileOpen,
        '-translate-x-full': !mobileOpen,
        'w-64': sidebarOpen,
        'w-16': !sidebarOpen,
    }">

    {{-- Logo --}}
    <div class="flex h-16 flex-shrink-0 items-center border-b border-gray-700 px-4">
        <div class="flex items-center gap-3 overflow-hidden">
            <div
                class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-blue-600 text-sm font-bold text-white">
                PA
            </div>
            <span class="overflow-hidden whitespace-nowrap text-sm font-semibold text-white transition-all duration-200"
                :class="sidebarOpen ? 'opacity-100 max-w-full' : 'lg:opacity-0 lg:max-w-0'">
                Prima Attendances
            </span>
        </div>
    </div>

    {{-- Navigation menu --}}
    <nav class="flex-1 overflow-y-auto px-2 py-4">

        {{-- Dashboard --}}
        <x-admin.sidebar-item :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')" label="Dashboard">
            <x-slot:icon>
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                </svg>
            </x-slot:icon>
        </x-admin.sidebar-item>

        {{-- ABSENSI --}}
        <x-admin.sidebar-group label="Absensi">
            <x-admin.sidebar-item :href="route('admin.attendances.index')" :active="request()->routeIs('admin.attendances.index')" label="Daftar Absensi">
                <x-slot:icon>
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </x-slot:icon>
            </x-admin.sidebar-item>

            @if ($isHrOrAdmin)
                <x-admin.sidebar-item :href="route('admin.attendances.manual-entry')" :active="request()->routeIs('admin.attendances.manual-entry')" label="Manual Entry">
                    <x-slot:icon>
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </x-slot:icon>
                </x-admin.sidebar-item>
            @endif

            <x-admin.sidebar-item :href="route('admin.attendances.calendar')" :active="request()->routeIs('admin.attendances.calendar')" label="Kalender">
                <x-slot:icon>
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                    </svg>
                </x-slot:icon>
            </x-admin.sidebar-item>
        </x-admin.sidebar-group>

        {{-- PENGAJUAN --}}
        <x-admin.sidebar-group label="Pengajuan">
            <x-admin.sidebar-item :href="route('admin.leaves.index')" :active="request()->routeIs('admin.leaves.*')" label="Cuti">
                <x-slot:icon>
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M6.115 5.19l.319 1.913A6 6 0 008.11 10.36L9.75 12l-.387.775c-.217.433-.132.956.21 1.298l1.348 1.348c.21.21.329.497.329.795v1.089c0 .426.24.815.622 1.006l.153.076c.433.217.956.132 1.298-.21l.723-.723a8.7 8.7 0 002.288-4.042 1.087 1.087 0 00-.358-1.099l-1.33-1.108c-.251-.21-.582-.299-.905-.245l-1.17.195a1.125 1.125 0 01-.98-.314l-.295-.295a1.125 1.125 0 010-1.591l.13-.132a1.125 1.125 0 011.3-.21l.603.302a.809.809 0 001.086-1.086L14.25 7.5l1.256-.837a4.5 4.5 0 001.528-1.732l.146-.292M6.115 5.19A9 9 0 1017.18 4.64M6.115 5.19A8.965 8.965 0 0112 3c1.929 0 3.716.607 5.18 1.64" />
                    </svg>
                </x-slot:icon>
            </x-admin.sidebar-item>

            <x-admin.sidebar-item :href="route('admin.permissions.index')" :active="request()->routeIs('admin.permissions.*')" label="Izin">
                <x-slot:icon>
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                </x-slot:icon>
            </x-admin.sidebar-item>

            @if ($isManagerPlus)
                <x-admin.sidebar-item :href="route('admin.approvals.index')" :active="request()->routeIs('admin.approvals.*')" label="Approval Center">
                    <x-slot:icon>
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </x-slot:icon>
                </x-admin.sidebar-item>
            @endif
        </x-admin.sidebar-group>

        {{-- KARYAWAN (hr, admin) --}}
        @if ($isHrOrAdmin)
            <x-admin.sidebar-group label="Karyawan">
                <x-admin.sidebar-item :href="route('admin.employees.index')" :active="request()->routeIs('admin.employees.*')" label="Daftar Karyawan">
                    <x-slot:icon>
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                        </svg>
                    </x-slot:icon>
                </x-admin.sidebar-item>

                <x-admin.sidebar-item :href="route('admin.master.departments')" :active="request()->routeIs('admin.master.departments')" label="Departemen">
                    <x-slot:icon>
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                        </svg>
                    </x-slot:icon>
                </x-admin.sidebar-item>
            </x-admin.sidebar-group>
        @endif

        {{-- MASTER DATA (hr, admin) --}}
        @if ($isHrOrAdmin)
            <x-admin.sidebar-group label="Master Data">
                <x-admin.sidebar-item :href="route('admin.master.office-locations')" :active="request()->routeIs('admin.master.office-locations')" label="Lokasi Kantor">
                    <x-slot:icon>
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                        </svg>
                    </x-slot:icon>
                </x-admin.sidebar-item>

                <x-admin.sidebar-item :href="route('admin.master.shifts')" :active="request()->routeIs('admin.master.shifts')" label="Shift">
                    <x-slot:icon>
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </x-slot:icon>
                </x-admin.sidebar-item>

                <x-admin.sidebar-item :href="route('admin.master.leave-types')" :active="request()->routeIs('admin.master.leave-types')" label="Jenis Cuti">
                    <x-slot:icon>
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z" />
                        </svg>
                    </x-slot:icon>
                </x-admin.sidebar-item>

                <x-admin.sidebar-item :href="route('admin.master.holidays')" :active="request()->routeIs('admin.master.holidays')" label="Hari Libur">
                    <x-slot:icon>
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                        </svg>
                    </x-slot:icon>
                </x-admin.sidebar-item>
            </x-admin.sidebar-group>
        @endif

        {{-- JADWAL SHIFT (hr, admin) --}}
        @if ($isHrOrAdmin)
            <x-admin.sidebar-group label="Jadwal">
                <x-admin.sidebar-item :href="route('admin.shift-assignments')" :active="request()->routeIs('admin.shift-assignments')" label="Jadwal Shift">
                    <x-slot:icon>
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5m-9-6h.008v.008H12v-.008zM12 15h.008v.008H12V15zm0 2.25h.008v.008H12v-.008zM9.75 15h.008v.008H9.75V15zm0 2.25h.008v.008H9.75v-.008zM7.5 15h.008v.008H7.5V15zm0 2.25h.008v.008H7.5v-.008zm6.75-4.5h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V15zm0 2.25h.008v.008h-.008v-.008zm2.25-4.5h.008v.008H16.5v-.008zm0 2.25h.008v.008H16.5V15z" />
                        </svg>
                    </x-slot:icon>
                </x-admin.sidebar-item>
            </x-admin.sidebar-group>
        @endif

        {{-- LAPORAN --}}
        <x-admin.sidebar-group label="Laporan">
            <x-admin.sidebar-item :href="route('admin.reports.attendance')" :active="request()->routeIs('admin.reports.attendance')" label="Absensi">
                <x-slot:icon>
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                    </svg>
                </x-slot:icon>
            </x-admin.sidebar-item>

            <x-admin.sidebar-item :href="route('admin.reports.leaves')" :active="request()->routeIs('admin.reports.leaves')" label="Cuti">
                <x-slot:icon>
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M10.5 6a7.5 7.5 0 107.5 7.5h-7.5V6z" />
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M13.5 10.5H21A7.5 7.5 0 0013.5 3v7.5z" />
                    </svg>
                </x-slot:icon>
            </x-admin.sidebar-item>

            <x-admin.sidebar-item :href="route('admin.reports.late-arrivals')" :active="request()->routeIs('admin.reports.late-arrivals')" label="Keterlambatan">
                <x-slot:icon>
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                </x-slot:icon>
            </x-admin.sidebar-item>

            <x-admin.sidebar-item :href="route('admin.reports.working-hours')" :active="request()->routeIs('admin.reports.working-hours')" label="Jam Kerja">
                <x-slot:icon>
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </x-slot:icon>
            </x-admin.sidebar-item>
        </x-admin.sidebar-group>

        {{-- NOTIFIKASI --}}
        <x-admin.sidebar-item :href="route('admin.notifications')" :active="request()->routeIs('admin.notifications')" label="Notifikasi">
            <x-slot:icon>
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                </svg>
            </x-slot:icon>
        </x-admin.sidebar-item>

        {{-- PENGATURAN (admin only) --}}
        @if ($isAdmin)
            <x-admin.sidebar-group label="Pengaturan">
                <x-admin.sidebar-item :href="route('admin.settings.company')" :active="request()->routeIs('admin.settings.company')" label="Perusahaan">
                    <x-slot:icon>
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </x-slot:icon>
                </x-admin.sidebar-item>

                <x-admin.sidebar-item :href="route('admin.settings.users')" :active="request()->routeIs('admin.settings.users')" label="User & Role">
                    <x-slot:icon>
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                        </svg>
                    </x-slot:icon>
                </x-admin.sidebar-item>

                <x-admin.sidebar-item :href="route('admin.settings.audit-logs')" :active="request()->routeIs('admin.settings.audit-logs')" label="Audit Log">
                    <x-slot:icon>
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
                        </svg>
                    </x-slot:icon>
                </x-admin.sidebar-item>
            </x-admin.sidebar-group>
        @endif

    </nav>

    {{-- Desktop collapse toggle --}}
    <div class="hidden flex-shrink-0 items-center border-t border-gray-700 p-2 lg:flex"
        :class="sidebarOpen ? 'justify-end' : 'justify-center'">
        <button @click="sidebarOpen = !sidebarOpen"
            class="rounded-md p-1.5 text-gray-400 transition-colors hover:bg-gray-700 hover:text-white"
            :title="sidebarOpen ? 'Tutup sidebar' : 'Buka sidebar'">
            <svg x-show="sidebarOpen" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M18.75 19.5l-7.5-7.5 7.5-7.5m-6 15L5.25 12l7.5-7.5" />
            </svg>
            <svg x-show="!sidebarOpen" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M11.25 4.5l7.5 7.5-7.5 7.5m-6-15l7.5 7.5-7.5 7.5" />
            </svg>
        </button>
    </div>
</aside>
