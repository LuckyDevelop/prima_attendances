<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Leave;

use App\Enums\LeaveStatus;
use App\Enums\UserRole;
use App\Livewire\Admin\AdminComponent;
use App\Models\Department;
use App\Repositories\Contracts\DepartmentRepositoryInterface;
use App\Repositories\Contracts\LeaveRequestRepositoryInterface;
use App\Repositories\Contracts\LeaveTypeRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
#[Title('Daftar Cuti')]
class Index extends AdminComponent
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $leaveTypeId = '';

    #[Url]
    public string $departmentId = '';

    #[Url]
    public string $dateFrom = '';

    #[Url]
    public string $dateTo = '';

    public int $perPage = 15;

    protected LeaveRequestRepositoryInterface $leaveRepo;
    protected LeaveTypeRepositoryInterface $leaveTypeRepo;
    protected DepartmentRepositoryInterface $deptRepo;

    public function boot(
        LeaveRequestRepositoryInterface $leaveRepo,
        LeaveTypeRepositoryInterface $leaveTypeRepo,
        DepartmentRepositoryInterface $deptRepo,
    ): void {
        $this->leaveRepo     = $leaveRepo;
        $this->leaveTypeRepo = $leaveTypeRepo;
        $this->deptRepo      = $deptRepo;
    }

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedStatus(): void { $this->resetPage(); }
    public function updatedLeaveTypeId(): void { $this->resetPage(); }
    public function updatedDepartmentId(): void { $this->resetPage(); }
    public function updatedDateFrom(): void { $this->resetPage(); }
    public function updatedDateTo(): void { $this->resetPage(); }

    public function resetFilters(): void
    {
        $this->reset(['search', 'status', 'leaveTypeId', 'departmentId', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    private function buildFilters(): array
    {
        $user    = $this->authUser();
        $filters = [
            'search'        => $this->search ?: null,
            'status'        => $this->status ?: null,
            'leave_type_id' => $this->leaveTypeId ?: null,
            'department_id' => $this->departmentId ?: null,
            'date_from'     => $this->dateFrom ?: null,
            'date_to'       => $this->dateTo ?: null,
            'per_page'      => $this->perPage,
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

    public function getLeaves(): LengthAwarePaginator
    {
        return $this->leaveRepo->paginateForAdmin(
            $this->authUser()->company_id,
            $this->buildFilters()
        );
    }

    public function getDepartments(): Collection
    {
        return $this->deptRepo->getByCompany($this->authUser()->company_id);
    }

    public function getLeaveTypes(): Collection
    {
        return $this->leaveTypeRepo->getByCompany($this->authUser()->company_id);
    }

    public function render(): View
    {
        return view('livewire.admin.leave.index', [
            'leaves'     => $this->getLeaves(),
            'leaveTypes' => $this->getLeaveTypes(),
            'departments'=> $this->getDepartments(),
            'statuses'   => LeaveStatus::cases(),
        ]);
    }
}
