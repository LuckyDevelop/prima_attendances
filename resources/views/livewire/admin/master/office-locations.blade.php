<div>
    <x-admin.page-header title="Lokasi Kantor">
        <x-slot:actions>
            <x-ui.button wire:click="openCreate">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Tambah Lokasi
            </x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse($locations as $loc)
        <x-ui.card wire:key="loc-{{ $loc->id }}">
            <div class="flex items-start justify-between gap-3">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <h3 class="text-sm font-semibold text-gray-900 truncate">{{ $loc->name }}</h3>
                        <x-ui.badge :status="$loc->is_active ? 'active' : 'inactive'" size="sm">
                            {{ $loc->is_active ? 'Aktif' : 'Nonaktif' }}
                        </x-ui.badge>
                    </div>

                    @if($loc->address)
                    <p class="mt-1 text-xs text-gray-500 line-clamp-2">{{ $loc->address }}</p>
                    @endif

                    <div class="mt-3 space-y-1">
                        @if($loc->latitude && $loc->longitude)
                        <p class="text-xs text-gray-500">
                            <span class="font-medium">Koordinat:</span>
                            {{ number_format($loc->latitude, 6) }}, {{ number_format($loc->longitude, 6) }}
                        </p>
                        @endif
                        <p class="text-xs text-gray-500">
                            <span class="font-medium">Radius:</span> {{ $loc->radius_meters }} meter
                        </p>
                    </div>
                </div>

                <div x-data="{ open: false }" class="relative flex-shrink-0">
                    <button @click="open = !open" @click.outside="open = false"
                            class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5zM12 12.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5zM12 18.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5z" />
                        </svg>
                    </button>
                    <div x-show="open" x-transition
                         class="absolute right-0 z-10 mt-1 w-36 rounded-xl border border-gray-200 bg-white py-1 shadow-lg">
                        <button @click="open = false" wire:click="openEdit({{ $loc->id }})"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50">Edit</button>
                        <button @click="open = false" wire:click="delete({{ $loc->id }})"
                                wire:confirm="Hapus lokasi '{{ $loc->name }}'?"
                                class="w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50">Hapus</button>
                    </div>
                </div>
            </div>
        </x-ui.card>
        @empty
        <div class="sm:col-span-2 lg:col-span-3">
            <x-ui.card>
                <x-admin.empty-state title="Belum ada lokasi kantor"
                    description="Tambahkan lokasi kantor untuk digunakan saat check-in karyawan." />
            </x-ui.card>
        </div>
        @endforelse
    </div>

    {{-- Modal form with Leaflet map --}}
    <x-ui.modal name="location-form" max-width="2xl">
        <x-slot:title>{{ $editingId ? 'Edit Lokasi Kantor' : 'Tambah Lokasi Kantor' }}</x-slot:title>

        <div class="space-y-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Nama Lokasi <span class="text-red-500">*</span>
                    </label>
                    <input wire:model="name" type="text"
                           class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                           placeholder="Contoh: Kantor Pusat Jakarta" />
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Alamat</label>
                    <textarea wire:model="address" rows="2"
                              class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                              placeholder="Alamat lengkap kantor"></textarea>
                </div>
            </div>

            {{-- Leaflet Map --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Pilih Lokasi di Peta</label>
                <div id="location-map" class="h-64 w-full rounded-lg border border-gray-200 bg-gray-100 z-0"></div>
                <p class="mt-1 text-xs text-gray-400">Klik atau drag marker untuk memilih koordinat.</p>
            </div>

            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Latitude</label>
                    <input wire:model.live="latitude" type="text"
                           class="w-full rounded-lg border-gray-300 text-sm font-mono shadow-sm focus:border-blue-500 focus:ring-blue-500"
                           placeholder="-6.200000" />
                    @error('latitude') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Longitude</label>
                    <input wire:model.live="longitude" type="text"
                           class="w-full rounded-lg border-gray-300 text-sm font-mono shadow-sm focus:border-blue-500 focus:ring-blue-500"
                           placeholder="106.816666" />
                    @error('longitude') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Radius (meter) <span class="text-red-500">*</span>
                    </label>
                    <input wire:model.live="radiusMeters" type="number" min="10" max="5000"
                           class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                    @error('radiusMeters') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <label class="flex items-center gap-3 cursor-pointer">
                <input wire:model="isActive" type="checkbox"
                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
                <span class="text-sm text-gray-700">Lokasi aktif</span>
            </label>
        </div>

        <x-slot:footer>
            <button @click="$dispatch('close-modal', { name: 'location-form' })"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                Batal
            </button>
            <x-ui.button wire:click="save">Simpan</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function () {
    let map = null;
    let marker = null;
    let circle = null;

    function initMap(lat, lng, radius) {
        if (map) {
            map.remove();
            map = null;
        }

        map = L.map('location-map').setView([lat, lng], 15);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        marker = L.marker([lat, lng], { draggable: true }).addTo(map);
        circle = L.circle([lat, lng], { radius: radius, color: '#3b82f6', fillOpacity: 0.1 }).addTo(map);

        function onMove(latlng) {
            circle.setLatLng(latlng);
            @this.updateCoords(latlng.lat, latlng.lng);
        }

        marker.on('dragend', (e) => onMove(e.target.getLatLng()));
        map.on('click', (e) => { marker.setLatLng(e.latlng); onMove(e.latlng); });
    }

    // Listen for Livewire dispatch
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('map-init', ({ lat, lng, radius }) => {
            // Wait for modal to be visible
            setTimeout(() => {
                initMap(lat, lng, radius);
            }, 150);
        });
    });

    // Update circle radius when Livewire property changes
    document.addEventListener('livewire:update', () => {
        if (circle && map) {
            const r = parseInt(@this.radiusMeters) || 100;
            circle.setRadius(r);
        }
    });
})();
</script>
@endpush
