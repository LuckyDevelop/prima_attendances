<div>
    @php $user = auth()->user(); @endphp

    <x-admin.page-header title="Profil Saya">
        <x-slot:actions>
            @if(!$editing)
            <x-ui.button variant="secondary" wire:click="startEditing">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                </svg>
                Edit Profil
            </x-ui.button>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- Avatar card --}}
        <div class="lg:col-span-1">
            <x-ui.card>
                <div class="flex flex-col items-center text-center">
                    <div class="relative mb-4">
                        <x-ui.avatar
                            :name="$user->full_name"
                            :src="$user->photo ? Storage::url($user->photo) : null"
                            class="h-24 w-24" />
                    </div>

                    <h2 class="text-base font-semibold text-gray-900">{{ $user->full_name }}</h2>
                    <p class="text-sm text-gray-500">{{ $user->employee_id }}</p>
                    <x-ui.badge :status="$user->role->value" class="mt-2">
                        {{ $user->role->label() }}
                    </x-ui.badge>

                    <div class="mt-6 w-full border-t border-gray-100 pt-5">
                        <form wire:submit="uploadPhoto" class="space-y-3">
                            <label for="photo-upload"
                                   class="block cursor-pointer rounded-lg border-2 border-dashed border-gray-300 p-4 text-center hover:border-blue-400 transition-colors">
                                <svg class="mx-auto h-8 w-8 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                                </svg>
                                <span class="mt-1 block text-xs text-gray-500">
                                    @if($photo)
                                        {{ $photo->getClientOriginalName() }}
                                    @else
                                        Klik untuk unggah foto
                                    @endif
                                </span>
                                <input id="photo-upload" type="file" class="hidden" wire:model="photo" accept="image/*">
                            </label>

                            @error('photo')
                            <p class="text-xs text-red-600">{{ $message }}</p>
                            @enderror

                            @if($photo)
                            <x-ui.button type="submit" variant="primary" class="w-full">
                                Simpan Foto
                            </x-ui.button>
                            @endif
                        </form>
                    </div>
                </div>
            </x-ui.card>
        </div>

        {{-- Profile info card --}}
        <div class="lg:col-span-2">
            <x-ui.card>
                <x-slot:header>
                    <h3 class="text-sm font-semibold text-gray-900">
                        {{ $editing ? 'Edit Profil' : 'Informasi Profil' }}
                    </h3>
                </x-slot:header>

                @if($editing)
                {{-- Edit form --}}
                <form wire:submit="save" class="space-y-4">
                    <x-ui.input
                        label="Nama Lengkap"
                        name="fullName"
                        wire:model="fullName"
                        :error="$errors->first('fullName')"
                        required />

                    <x-ui.input
                        label="Nomor Telepon"
                        name="phone"
                        wire:model="phone"
                        :error="$errors->first('phone')"
                        placeholder="+62..." />

                    <div class="flex justify-end gap-2 pt-2">
                        <x-ui.button variant="secondary" type="button" wire:click="cancelEditing">
                            Batal
                        </x-ui.button>
                        <x-ui.button variant="primary" type="submit" wire:click="save">
                            Simpan
                        </x-ui.button>
                    </div>
                </form>

                @else
                {{-- Read-only view --}}
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-medium text-gray-500">Nama Lengkap</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $user->full_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500">Email</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $user->email }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500">NIK Karyawan</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $user->employee_id }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500">Telepon</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $user->phone ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500">Departemen</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $user->department?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500">Lokasi Kantor</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $user->officeLocation?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500">Role</dt>
                        <dd class="mt-1"><x-ui.badge :status="$user->role->value">{{ $user->role->label() }}</x-ui.badge></dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500">Status</dt>
                        <dd class="mt-1"><x-ui.badge :status="$user->status->value">{{ ucfirst($user->status->value) }}</x-ui.badge></dd>
                    </div>
                </dl>

                <div class="mt-6 border-t border-gray-100 pt-4">
                    <a href="{{ route('admin.profile.password') }}"
                       class="text-sm text-blue-600 hover:underline">
                        Ganti password →
                    </a>
                </div>
                @endif
            </x-ui.card>
        </div>
    </div>
</div>
