<div>
    <x-admin.page-header title="Ganti Password">
        <x-slot:actions>
            <x-ui.button variant="secondary" :href="route('admin.profile')">
                ← Kembali ke Profil
            </x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="max-w-lg">
        <x-ui.card>
            <x-slot:header>
                <h3 class="text-sm font-semibold text-gray-900">Ubah Password Akun</h3>
            </x-slot:header>

            <form wire:submit="save" class="space-y-4">
                <x-ui.input
                    label="Password Saat Ini"
                    name="currentPassword"
                    type="password"
                    wire:model="currentPassword"
                    :error="$errors->first('currentPassword')"
                    required />

                <x-ui.input
                    label="Password Baru"
                    name="newPassword"
                    type="password"
                    wire:model="newPassword"
                    :error="$errors->first('newPassword')"
                    hint="Minimal 8 karakter."
                    required />

                <x-ui.input
                    label="Konfirmasi Password Baru"
                    name="newPasswordConfirm"
                    type="password"
                    wire:model="newPasswordConfirm"
                    :error="$errors->first('newPasswordConfirm')"
                    required />

                <div class="flex justify-end pt-2">
                    <x-ui.button type="submit" variant="primary" wire:click="save">
                        Simpan Password
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
</div>
