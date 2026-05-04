<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Permission;

use App\Enums\LeaveStatus;
use App\Enums\PermissionType;
use App\Enums\UserRole;
use App\Livewire\Admin\AdminComponent;
use App\Models\Department;
use App\Repositories\Contracts\DepartmentRepositoryInterface;
use App\Repositories\Contracts\PermissionRequestRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
#[Title('Daftar Izin')]
class Index extends AdminComponent
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $permissionType = '';

    #[Url]
    public string $departmentId = '';

    #[Url]
    public string $dateFrom = '';

    #[Url]
    public string $dateTo = '';

    public int $perPage = 15;

    protected PermissionRequestRepositoryInterface $permRepo;
    protected DepartmentRepositoryInterface $deptRepo;

    public function boot(
        PermissionRequestRepositoryInterface $permRepo,
        DepartmentRepositoryInterface $deptRepo,
    ): void {
        $this->permRepo = $permRepo;
        $this->deptRepo = $deptRepo;
    }

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedStatus(): void { $this->resetPage(); }
    public function updatedPermissionType(): void { $this->resetPage(); }
    public function updatedDepartmentId(): void { $this->resetPage(); }
    public function updatedDateFrom(): void { $this->resetPage(); }
    public function updatedDateTo(): void { $this->resetPage(); }

    public function resetFilters(): void
    {
        $this->reset(['search', 'status', 'permissionType', 'departmentId', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    private function buildFilters(): array
    {
        $user    = $this->authUser();
        $filters = [
            'search'          => $this->search ?: null,
            'status'          => $this->status ?: null,
            'permission_type' => $this->permissionType ?: null,
            'department_id'   => $this->departmentId ?: null,
            'date_from'       => $this->dateFrom ?: null,
            'date_to'         => $this->dateTo ?: null,
            'per_page'        => $this->perPage,
        ];

        if ($user->role === UserRole::MANAGER) {
            $deptIds = Department::where('manager_id', $user->id)
                ->where('company_id', $user->company_id)
                ->pluck('id')
                ->toArray();
            $filters['dept_ids'] = $deptIds;
        }

        return $filters;
    }

    public function getPermissions(): LengthAwarePaginator
    {
        return $this->permRepo->paginateForAdmin(
            $this->authUser()->company_id,
            $this->buildFilters()
        );
    }

    public function getDepartments(): Collection
    {
        return $this->deptRepo->getByCompany($this->authUser()->company_id);
    }

    public function render(): View
    {
        return view('livewire.admin.permission.index', [
            'permissions'     => $this->getPermissions(),
            'departments'     => $this->getDepartments(),
            'statuses'        => LeaveStatus::cases(),
            'permissionTypes' => PermissionType::cases(),
        ]);
    }
}
