<div>
    <x-admin.page-header :title="$pageTitle">
        <x-slot:actions>
            <x-ui.button variant="secondary" href="{{ route('admin.employees.index') }}" wire:navigate>
                Batal
            </x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    <form wire:submit="save" class="space-y-6">

        {{-- Data Pribadi --}}
        <x-ui.card>
            <x-slot:header>
                <h3 class="text-sm font-semibold text-gray-900">Data Pribadi</h3>
            </x-slot:header>

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">

                {{-- Photo upload --}}
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Foto Profil</label>
                    <div class="flex items-center gap-4">
                        <div
                            class="h-20 w-20 flex-shrink-0 overflow-hidden rounded-full bg-gray-100 border border-gray-200">
                            @if ($photo)
                                <img src="{{ $photo->temporaryUrl() }}" class="h-full w-full object-cover"
                                    alt="Preview" />
                            @elseif($existingPhoto)
                                <img src="{{ Storage::url($existingPhoto) }}" class="h-full w-full object-cover"
                                    alt="Foto" />
                            @else
                                <svg class="h-full w-full text-gray-300 p-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path
                                        d="M24 20.993V24H0v-2.996A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            @endif
                        </div>
                        <div>
                            <label
                                class="cursor-pointer inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                                </svg>
                                Upload Foto
                                <input wire:model="photo" type="file" accept="image/*" class="hidden" />
                            </label>
                            @error('photo')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-xs text-gray-400">JPG, PNG. Maks 2MB.</p>
                        </div>
                    </div>
                </div>

                {{-- Full Name --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Nama Lengkap <span class="text-red-500">*</span>
                    </label>
                    <input wire:model="fullName" type="text"
                        class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        placeholder="Nama lengkap karyawan" />
                    @error('fullName')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Employee ID --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        NIK / Employee ID <span class="text-red-500">*</span>
                    </label>
                    <input wire:model="employeeId" type="text"
                        class="w-full rounded-lg border-gray-300 text-sm font-mono shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        placeholder="Contoh: EMP001" />
                    @error('employeeId')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Email --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Email <span class="text-red-500">*</span>
                    </label>
                    <input wire:model="email" type="email"
                        class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        placeholder="email@perusahaan.com" />
                    @error('email')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Phone --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Telepon</label>
                    <input wire:model="phone" type="text"
                        class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        placeholder="08xxxxxxxxxx" />
                    @error('phone')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </x-ui.card>

        {{-- Data Kepegawaian --}}
        <x-ui.card>
            <x-slot:header>
                <h3 class="text-sm font-semibold text-gray-900">Data Kepegawaian</h3>
            </x-slot:header>

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">

                {{-- Department --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Departemen</label>
                    <select wire:model="departmentId"
                        class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">— Pilih Departemen —</option>
                        @foreach ($departments as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                        @endforeach
                    </select>
                    @error('departmentId')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Office Location --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Lokasi Kantor</label>
                    <select wire:model="officeLocationId"
                        class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">— Pilih Lokasi —</option>
                        @foreach ($locations as $loc)
                            <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                        @endforeach
                    </select>
                    @error('officeLocationId')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Role --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Role <span class="text-red-500">*</span>
                    </label>
                    <select wire:model="role"
                        class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">— Pilih Role —</option>
                        @foreach ($roles as $r)
                            <option value="{{ $r->value }}">{{ $r->label() }}</option>
                        @endforeach
                    </select>
                    @error('role')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Status --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Status <span class="text-red-500">*</span>
                    </label>
                    <select wire:model="status"
                        class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @foreach ($statuses as $s)
                            <option value="{{ $s->value }}">{{ ucfirst($s->value) }}</option>
                        @endforeach
                    </select>
                    @error('status')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </x-ui.card>

        {{-- Akun --}}
        <x-ui.card>
            <x-slot:header>
                <h3 class="text-sm font-semibold text-gray-900">
                    Akun
                    @if ($userId)
                        <span class="ml-2 text-xs font-normal text-gray-400">(Kosongkan jika tidak ingin mengubah
                            password)</span>
                    @endif
                </h3>
            </x-slot:header>

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Password @if (!$userId)
                            <span class="text-red-500">*</span>
                        @endif
                    </label>
                    <input wire:model="password" type="password"
                        class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        placeholder="{{ $userId ? 'Kosongkan jika tidak diubah' : 'Min. 8 karakter' }}" />
                    @error('password')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Konfirmasi Password @if (!$userId)
                            <span class="text-red-500">*</span>
                        @endif
                    </label>
                    <input wire:model="password_confirmation" type="password"
                        class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        placeholder="Ulangi password" />
                </div>
            </div>
        </x-ui.card>

        {{-- Actions --}}
        <div class="flex justify-end gap-3">
            <x-ui.button variant="secondary" href="{{ route('admin.employees.index') }}" wire:navigate>
                Batal
            </x-ui.button>
            <x-ui.button type="submit" wire:loading.attr="disabled">
                {{ $userId ? 'Simpan Perubahan' : 'Tambah Karyawan' }}
            </x-ui.button>
        </div>

    </form>
</div>
