<div>
    <x-admin.page-header :title="'Absensi — ' .
        ($attendance->user?->full_name ?? '?') .
        ' (' .
        \Carbon\Carbon::parse($attendance->work_date)->format('d M Y') .
        ')'">
        <x-slot:actions>
            <a href="{{ route('admin.attendances.index') }}"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                ← Kembali
            </a>
            <a href="{{ route('admin.attendances.manual-entry') }}?user_id={{ $attendance->user_id }}&date={{ $attendance->work_date }}"
                class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                Edit / Koreksi
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="space-y-6">

        {{-- Check-In / Check-Out Cards --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

            {{-- Check-In --}}
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <h3 class="mb-4 text-base font-semibold text-gray-900">Check-In</h3>
                <div class="flex gap-4">
                    @if ($attendance->check_in_selfie)
                        <div class="h-24 w-24 flex-shrink-0 overflow-hidden rounded-xl bg-gray-100">
                            <img src="{{ Storage::url($attendance->check_in_selfie) }}"
                                class="h-full w-full object-cover" alt="Selfie Check-in" />
                        </div>
                    @else
                        <div
                            class="flex h-24 w-24 flex-shrink-0 items-center justify-center rounded-xl bg-gray-100 text-gray-400">
                            <svg class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="1.5"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                            </svg>
                        </div>
                    @endif
                    <div class="space-y-2 text-sm">
                        <div>
                            <span class="text-gray-500">Waktu:</span>
                            <span class="ml-2 font-semibold text-gray-900">
                                {{ $attendance->check_in_time ? \Carbon\Carbon::parse($attendance->check_in_time)->format('H:i:s') : '—' }}
                            </span>
                        </div>
                        <div>
                            <span class="text-gray-500">Lokasi:</span>
                            <span class="ml-2 text-gray-700">{{ $attendance->officeLocation?->name ?? '—' }}</span>
                        </div>
                        @if ($attendance->check_in_lat && $attendance->check_in_lng)
                            <div>
                                <span class="text-gray-500">Koordinat:</span>
                                <span class="ml-2 font-mono text-xs text-gray-600">
                                    {{ $attendance->check_in_lat }}, {{ $attendance->check_in_lng }}
                                </span>
                            </div>
                        @endif
                        <div class="flex gap-2 pt-1">
                            @if ($attendance->is_mock_location)
                                <span
                                    class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700">
                                    ⚠ Mock GPS
                                </span>
                            @endif
                            @if ($attendance->face_verified)
                                <span
                                    class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700">
                                    Wajah ✓
                                </span>
                            @else
                                <span
                                    class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">
                                    Wajah ✗
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Check-Out --}}
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <h3 class="mb-4 text-base font-semibold text-gray-900">Check-Out</h3>
                @if ($attendance->check_out_time)
                    <div class="flex gap-4">
                        @if ($attendance->check_out_selfie)
                            <div class="h-24 w-24 flex-shrink-0 overflow-hidden rounded-xl bg-gray-100">
                                <img src="{{ Storage::url($attendance->check_out_selfie) }}"
                                    class="h-full w-full object-cover" alt="Selfie Check-out" />
                            </div>
                        @else
                            <div
                                class="flex h-24 w-24 flex-shrink-0 items-center justify-center rounded-xl bg-gray-100 text-gray-400">
                                <svg class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="1.5"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                                </svg>
                            </div>
                        @endif
                        <div class="space-y-2 text-sm">
                            <div>
                                <span class="text-gray-500">Waktu:</span>
                                <span class="ml-2 font-semibold text-gray-900">
                                    {{ \Carbon\Carbon::parse($attendance->check_out_time)->format('H:i:s') }}
                                </span>
                            </div>
                            @if ($attendance->check_out_lat && $attendance->check_out_lng)
                                <div>
                                    <span class="text-gray-500">Koordinat:</span>
                                    <span class="ml-2 font-mono text-xs text-gray-600">
                                        {{ $attendance->check_out_lat }}, {{ $attendance->check_out_lng }}
                                    </span>
                                </div>
                            @endif
                            <div>
                                <span class="text-gray-500">Durasi Kerja:</span>
                                <span class="ml-2 font-semibold text-gray-900">
                                    {{ floor($attendance->work_duration_min / 60) }}j
                                    {{ $attendance->work_duration_min % 60 }}m
                                </span>
                            </div>
                        </div>
                    </div>
                @else
                    <p class="text-sm text-gray-400">Belum check-out.</p>
                @endif
            </div>
        </div>

        {{-- Map --}}
        @if ($attendance->check_in_lat && $attendance->check_in_lng)
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <h3 class="mb-4 text-base font-semibold text-gray-900">Peta Lokasi</h3>
                <div id="attendance-map" class="h-72 w-full rounded-lg bg-gray-100"
                    data-checkin-lat="{{ $attendance->check_in_lat }}"
                    data-checkin-lng="{{ $attendance->check_in_lng }}"
                    data-checkout-lat="{{ $attendance->check_out_lat ?? '' }}"
                    data-checkout-lng="{{ $attendance->check_out_lng ?? '' }}"
                    data-office-lat="{{ $attendance->officeLocation?->latitude ?? '' }}"
                    data-office-lng="{{ $attendance->officeLocation?->longitude ?? '' }}"
                    data-office-radius="{{ $attendance->officeLocation?->radius_meters ?? 100 }}">
                </div>
            </div>
        @endif

        {{-- Notes --}}
        @if ($attendance->notes)
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <h3 class="mb-2 text-base font-semibold text-gray-900">Catatan</h3>
                <p class="text-sm text-gray-700">{{ $attendance->notes }}</p>
            </div>
        @endif

        {{-- Audit Trail --}}
        @if ($auditLogs->isNotEmpty())
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <h3 class="mb-4 text-base font-semibold text-gray-900">Riwayat Perubahan</h3>
                <ol class="relative border-l border-gray-200 pl-4 space-y-4">
                    @foreach ($auditLogs as $log)
                        <li class="ml-2">
                            <div
                                class="absolute -left-1.5 mt-1.5 h-3 w-3 rounded-full border-2 border-white bg-blue-400">
                            </div>
                            <p class="text-sm font-medium text-gray-900">{{ $log->action }}</p>
                            <p class="text-xs text-gray-500">
                                {{ $log->created_at->diffForHumans() }}
                                @if ($log->ip_address)
                                    · IP: {{ $log->ip_address }}
                                @endif
                            </p>
                            @if ($log->metadata)
                                <pre class="mt-1 overflow-x-auto rounded bg-gray-50 p-2 text-xs text-gray-600">{{ json_encode($log->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </div>
        @endif

    </div>
</div>

@push('scripts')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const mapEl = document.getElementById('attendance-map');
            if (!mapEl) return;

            const checkInLat = parseFloat(mapEl.dataset.checkinLat);
            const checkInLng = parseFloat(mapEl.dataset.checkinLng);
            const checkOutLat = parseFloat(mapEl.dataset.checkoutLat);
            const checkOutLng = parseFloat(mapEl.dataset.checkoutLng);
            const officeLat = parseFloat(mapEl.dataset.officeLat);
            const officeLng = parseFloat(mapEl.dataset.officeLng);
            const officeRadius = parseInt(mapEl.dataset.officeRadius) || 100;

            if (isNaN(checkInLat) || isNaN(checkInLng)) return;

            const map = L.map('attendance-map').setView([checkInLat, checkInLng], 16);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);

            // Check-In marker (green)
            L.marker([checkInLat, checkInLng], {
                icon: L.divIcon({
                    className: '',
                    html: '<div style="width:14px;height:14px;background:#16a34a;border:2px solid #fff;border-radius:50%;box-shadow:0 1px 4px rgba(0,0,0,.3)"></div>',
                    iconSize: [14, 14],
                    iconAnchor: [7, 7]
                })
            }).addTo(map).bindPopup('Check-In');

            // Check-Out marker (red)
            if (!isNaN(checkOutLat) && !isNaN(checkOutLng)) {
                L.marker([checkOutLat, checkOutLng], {
                    icon: L.divIcon({
                        className: '',
                        html: '<div style="width:14px;height:14px;background:#dc2626;border:2px solid #fff;border-radius:50%;box-shadow:0 1px 4px rgba(0,0,0,.3)"></div>',
                        iconSize: [14, 14],
                        iconAnchor: [7, 7]
                    })
                }).addTo(map).bindPopup('Check-Out');
            }

            // Office circle (blue)
            if (!isNaN(officeLat) && !isNaN(officeLng)) {
                L.circle([officeLat, officeLng], {
                    radius: officeRadius,
                    color: '#2563eb',
                    fillOpacity: 0.1
                }).addTo(map);
                L.marker([officeLat, officeLng], {
                    icon: L.divIcon({
                        className: '',
                        html: '<div style="width:10px;height:10px;background:#2563eb;border:2px solid #fff;border-radius:50%"></div>',
                        iconSize: [10, 10],
                        iconAnchor: [5, 5]
                    })
                }).addTo(map).bindPopup('Kantor');
            }
        });
    </script>
@endpush
