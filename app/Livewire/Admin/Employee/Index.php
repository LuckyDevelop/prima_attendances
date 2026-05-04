<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Employee;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Repositories\Contracts\DepartmentRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use App\Livewire\Admin\AdminComponent;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
#[Title('Daftar Karyawan')]
class Index extends AdminComponent
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $departmentId = '';

    #[Url]
    public string $role = '';

    #[Url]
    public string $status = '';

    public int $perPage = 15;

    public ?int $deletingId = null;

    protected UserRepositoryInterface $userRepo;
    protected DepartmentRepositoryInterface $deptRepo;

    public function boot(
        UserRepositoryInterface $userRepo,
        DepartmentRepositoryInterface $deptRepo,
    ): void {
        $this->userRepo = $userRepo;
        $this->deptRepo = $deptRepo;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedDepartmentId(): void
    {
        $this->resetPage();
    }

    public function updatedRole(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'departmentId', 'role', 'status']);
        $this->resetPage();
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->dispatch('open-modal', name: 'delete-employee');
    }

    public function deleteEmployee(): void
    {
        if (! $this->deletingId) {
            return;
        }

        $user = $this->userRepo->findById($this->deletingId);

        if (! $user || $user->company_id !== $this->authUser()->company_id) {
            $this->deletingId = null;
            return;
        }

        if ($user->id === auth()->id()) {
            $this->dispatch('toast', type: 'error', message: 'Tidak dapat menghapus akun sendiri.');
            $this->deletingId = null;
            return;
        }

        $this->userRepo->delete($user);
        $this->deletingId = null;

        $this->dispatch('toast', type: 'success', message: 'Karyawan berhasil dihapus.');
    }

    public function toggleStatus(int $id): void
    {
        $user = $this->userRepo->findById($id);

        if (! $user || $user->company_id !== $this->authUser()->company_id) {
            return;
        }

        if ($user->id === auth()->id()) {
            $this->dispatch('toast', type: 'error', message: 'Tidak dapat mengubah status akun sendiri.');
            return;
        }

        $newStatus = $user->status === UserStatus::ACTIVE
            ? UserStatus::INACTIVE
            : UserStatus::ACTIVE;

        $this->userRepo->update($user, ['status' => $newStatus]);

        $label = $newStatus === UserStatus::ACTIVE ? 'diaktifkan' : 'dinonaktifkan';
        $this->dispatch('toast', type: 'success', message: "Karyawan berhasil {$label}.");
    }

    public function resetPassword(int $id): void
    {
        $user = $this->userRepo->findById($id);

        if (! $user || $user->company_id !== $this->authUser()->company_id) {
            return;
        }

        $newPassword = Str::random(10);
        $this->userRepo->updatePassword($user, Hash::make($newPassword));

        $this->dispatch('toast', type: 'success', message: "Password direset: {$newPassword}");
    }

    public function getDepartments(): Collection
    {
        return $this->deptRepo->getByCompany($this->authUser()->company_id);
    }

    public function getUsers(): LengthAwarePaginator
    {
        return $this->userRepo->paginate($this->authUser()->company_id, [
            'search'        => $this->search,
            'department_id' => $this->departmentId ?: null,
            'role'          => $this->role ?: null,
            'status'        => $this->status ?: null,
            'per_page'      => $this->perPage,
        ]);
    }

    public function render(): View
    {
        return view('livewire.admin.employee.index', [
            'users'       => $this->getUsers(),
            'departments' => $this->getDepartments(),
            'roles'       => UserRole::cases(),
            'statuses'    => UserStatus::cases(),
        ]);
    }
}
