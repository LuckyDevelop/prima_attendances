<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Settings;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Livewire\Admin\AdminComponent;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
#[Title('User & Role')]
class Users extends AdminComponent
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $role = '';

    #[Url]
    public string $status = '';

    public int $perPage = 15;

    protected UserRepositoryInterface $userRepo;

    public function boot(UserRepositoryInterface $userRepo): void
    {
        $this->userRepo = $userRepo;
    }

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedRole(): void   { $this->resetPage(); }
    public function updatedStatus(): void { $this->resetPage(); }

    public function getUsers(): LengthAwarePaginator
    {
        return $this->userRepo->paginate($this->authUser()->company_id, [
            'search'   => $this->search,
            'role'     => $this->role ?: null,
            'status'   => $this->status ?: null,
            'per_page' => $this->perPage,
        ]);
    }

    public function changeRole(int $userId, string $newRole): void
    {
        $user = $this->userRepo->findById($userId);

        if (! $user || $user->company_id !== $this->authUser()->company_id) {
            $this->dispatch('toast', type: 'error', message: 'Karyawan tidak ditemukan.');
            return;
        }

        // Prevent admin from removing their own admin role.
        if ($user->id === $this->authUser()->id && $newRole !== UserRole::ADMIN->value) {
            $this->dispatch('toast', type: 'error', message: 'Anda tidak dapat mengubah role Anda sendiri.');
            return;
        }

        try {
            $role = UserRole::from($newRole);
            $this->userRepo->updateRole($user, $role);
            $this->dispatch('toast', type: 'success', message: "Role {$user->full_name} diubah ke {$role->label()}.");
        } catch (\ValueError) {
            $this->dispatch('toast', type: 'error', message: 'Role tidak valid.');
        } catch (\Exception $e) {
            Log::error('Change role failed', ['admin_id' => $this->authUser()->id, 'error' => $e->getMessage()]);
            $this->dispatch('toast', type: 'error', message: 'Gagal mengubah role.');
        }
    }

    public function toggleStatus(int $userId): void
    {
        $user = $this->userRepo->findById($userId);

        if (! $user || $user->company_id !== $this->authUser()->company_id) {
            $this->dispatch('toast', type: 'error', message: 'Karyawan tidak ditemukan.');
            return;
        }

        if ($user->id === $this->authUser()->id) {
            $this->dispatch('toast', type: 'error', message: 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
            return;
        }

        try {
            $sv         = is_object($user->status) ? $user->status : UserStatus::from($user->status);
            $newStatus  = $sv === UserStatus::ACTIVE ? UserStatus::INACTIVE : UserStatus::ACTIVE;
            $this->userRepo->updateStatus($user, $newStatus);
            $label = $newStatus === UserStatus::ACTIVE ? 'diaktifkan' : 'dinonaktifkan';
            $this->dispatch('toast', type: 'success', message: "{$user->full_name} berhasil {$label}.");
        } catch (\Exception $e) {
            Log::error('Toggle status failed', ['admin_id' => $this->authUser()->id, 'error' => $e->getMessage()]);
            $this->dispatch('toast', type: 'error', message: 'Gagal mengubah status.');
        }
    }

    public function render(): View
    {
        return view('livewire.admin.settings.users', [
            'users'   => $this->getUsers(),
            'roles'   => UserRole::cases(),
            'statuses'=> UserStatus::cases(),
        ]);
    }
}
