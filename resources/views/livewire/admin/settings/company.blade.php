<div>
    <x-admin.page-header title="Pengaturan Perusahaan" />

    <div class="mx-auto max-w-2xl space-y-6">

        {{-- Logo Section --}}
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
            <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-500">Logo Perusahaan</h3>

            <div class="flex items-start gap-6">
                {{-- Current Logo --}}
                <div class="h-20 w-20 flex-shrink-0 overflow-hidden rounded-xl border border-gray-200 bg-gray-50">
                    @if($logoPath)
                        <img src="{{ Storage::url($logoPath) }}" class="h-full w-full object-contain p-1" alt="Logo" />
                    @else
                        <div class="flex h-full w-full items-center justify-center text-xs font-bold text-gray-400">
                            LOGO
                        </div>
                    @endif
                </div>

                <div class="flex-1 space-y-2">
                    {{-- Preview new upload --}}
                    @if($logo)
                        <div class="mb-2">
                            <p class="text-xs text-gray-500 mb-1">Preview:</p>
                            <img src="{{ $logo->temporaryUrl() }}" class="h-16 w-16 rounded-lg object-contain border border-gray-200 p-1" />
                        </div>
                    @endif

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Upload Logo Baru</label>
                        <input type="file" wire:model="logo" accept="image/*"
                               class="block w-full text-sm text-gray-500 file:mr-3 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-blue-700 hover:file:bg-blue-100" />
                        <p class="mt-1 text-xs text-gray-500">JPG, PNG, SVG — maks 2MB</p>
                        @error('logo') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    @if($logoPath && !$logo)
                        <button wire:click="removeLogo"
                                wire:confirm="Hapus logo perusahaan?"
                                class="text-xs text-red-600 hover:underline">
                            Hapus logo
                        </button>
                    @endif
                </div>
            </div>
        </div>

        {{-- Company Info --}}
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
            <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-500">Informasi Perusahaan</h3>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Nama Perusahaan <span class="text-red-500">*</span>
                    </label>
                    <input type="text" wire:model="name" maxlength="150"
                           class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Timezone <span class="text-red-500">*</span>
                    </label>
                    <select wire:model="timezone"
                            class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @foreach($timezones as $tz)
                            <option value="{{ $tz }}">{{ $tz }}</option>
                        @endforeach
                    </select>
                    @error('timezone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Toleransi Keterlambatan (menit)
                    </label>
                    <input type="number" wire:model="lateTolerance" min="0" max="120"
                           class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                    <p class="mt-1 text-xs text-gray-500">
                        Karyawan dianggap tepat waktu jika check-in dalam toleransi ini setelah jam masuk shift.
                    </p>
                    @error('lateTolerance') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Save Button --}}
        <div class="flex justify-end">
            <button wire:click="save" wire:loading.attr="disabled"
                    class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-60">
                <span wire:loading.remove>Simpan Perubahan</span>
                <span wire:loading><x-ui.spinner class="h-4 w-4" /> Menyimpan...</span>
            </button>
        </div>

    </div>
</div>
