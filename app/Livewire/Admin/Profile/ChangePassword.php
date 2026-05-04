<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Profile;

use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Livewire\Admin\AdminComponent;

#[Layout('layouts.admin')]
#[Title('Ganti Password')]
class ChangePassword extends AdminComponent
{
    public string $currentPassword    = '';
    public string $newPassword        = '';
    public string $newPasswordConfirm = '';

    public function __construct(
        protected UserRepositoryInterface $userRepo,
        protected AuditLogRepositoryInterface $auditRepo,
    ) {}

    public function save(): void
    {
        $this->validate([
            'currentPassword'    => ['required', 'current_password'],
            'newPassword'        => ['required', Password::min(8), 'different:currentPassword'],
            'newPasswordConfirm' => ['required', 'same:newPassword'],
        ], [
            'currentPassword.current_password' => 'Password saat ini tidak sesuai.',
            'newPassword.different'            => 'Password baru harus berbeda dari password saat ini.',
            'newPasswordConfirm.same'          => 'Konfirmasi password tidak cocok.',
        ]);

        DB::beginTransaction();
        try {
            $this->userRepo->updatePassword($this->authUser(), Hash::make($this->newPassword));

            $this->auditRepo->log(
                auth()->id(),
                'password_changed',
                'User',
                (string) auth()->id(),
                null,
                request()->ip(),
            );

            DB::commit();

            $this->reset(['currentPassword', 'newPassword', 'newPasswordConfirm']);
            $this->dispatch('toast', type: 'success', message: 'Password berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Password change failed', ['user_id' => auth()->id(), 'error' => $e->getMessage()]);
            $this->dispatch('toast', type: 'error', message: 'Gagal mengubah password.');
        }
    }

    public function render(): View
    {
        return view('livewire.admin.profile.change-password');
    }
}
