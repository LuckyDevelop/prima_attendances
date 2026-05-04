@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')

    <x-admin.page-header title="Dashboard">
        <x-slot:actions>
            <span class="text-sm text-gray-500">
                {{ now()->locale('id')->isoFormat('dddd, D MMMM YYYY') }}
            </span>
        </x-slot:actions>
    </x-admin.page-header>

    {{-- Stat cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-admin.stat-card label="Total Karyawan" value="—" color="blue">
            <x-slot:icon>
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                </svg>
            </x-slot:icon>
        </x-admin.stat-card>

        <x-admin.stat-card label="Hadir Hari Ini" value="—" color="green">
            <x-slot:icon>
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </x-slot:icon>
        </x-admin.stat-card>

        <x-admin.stat-card label="Terlambat" value="—" color="yellow">
            <x-slot:icon>
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </x-slot:icon>
        </x-admin.stat-card>

        <x-admin.stat-card label="Cuti Hari Ini" value="—" color="purple">
            <x-slot:icon>
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                </svg>
            </x-slot:icon>
        </x-admin.stat-card>
    </div>

    {{-- Placeholder content --}}
    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <x-ui.card>
                <x-slot:header>
                    <h3 class="text-sm font-semibold text-gray-900">Grafik Absensi (7 Hari Terakhir)</h3>
                </x-slot:header>
                <x-admin.empty-state title="Chart akan tersedia"
                    description="Livewire dashboard component akan diimplementasi di Phase 2." />
            </x-ui.card>
        </div>

        <div>
            <x-ui.card>
                <x-slot:header>
                    <h3 class="text-sm font-semibold text-gray-900">Pending Approvals</h3>
                </x-slot:header>
                <x-admin.empty-state title="Tidak ada pengajuan" description="Semua pengajuan telah diproses." />
            </x-ui.card>
        </div>
    </div>

@endsection
