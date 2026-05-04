<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Report;

use App\Livewire\Admin\AdminComponent;
use App\Repositories\Contracts\DepartmentRepositoryInterface;
use App\Repositories\Contracts\LeaveRequestRepositoryInterface;
use App\Repositories\Contracts\LeaveTypeRepositoryInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;

#[Layout('layouts.admin')]
#[Title('Laporan Cuti')]
class Leave extends AdminComponent
{
    #[Url]
    public string $dateFrom = '';

    #[Url]
    public string $dateTo = '';

    #[Url]
    public string $departmentId = '';

    #[Url]
    public string $leaveTypeId = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $search = '';

    protected LeaveRequestRepositoryInterface $leaveRepo;
    protected DepartmentRepositoryInterface $deptRepo;
    protected LeaveTypeRepositoryInterface $leaveTypeRepo;

    public function boot(
        LeaveRequestRepositoryInterface $leaveRepo,
        DepartmentRepositoryInterface $deptRepo,
        LeaveTypeRepositoryInterface $leaveTypeRepo,
    ): void {
        $this->leaveRepo     = $leaveRepo;
        $this->deptRepo      = $deptRepo;
        $this->leaveTypeRepo = $leaveTypeRepo;
    }

    public function mount(): void
    {
        if (empty($this->dateFrom)) {
            $this->dateFrom = today()->startOfMonth()->toDateString();
        }
        if (empty($this->dateTo)) {
            $this->dateTo = today()->toDateString();
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['departmentId', 'leaveTypeId', 'status', 'search']);
        $this->dateFrom = today()->startOfMonth()->toDateString();
        $this->dateTo   = today()->toDateString();
    }

    private function buildFilters(): array
    {
        return [
            'date_from'     => $this->dateFrom ?: null,
            'date_to'       => $this->dateTo ?: null,
            'department_id' => $this->departmentId ?: null,
            'leave_type_id' => $this->leaveTypeId ?: null,
            'status'        => $this->status ?: null,
            'search'        => $this->search ?: null,
        ];
    }

    public function getReport(): Collection
    {
        return $this->leaveRepo->getLeaveReport(
            $this->authUser()->company_id,
            $this->buildFilters()
        );
    }

    public function getDepartments(): EloquentCollection
    {
        return $this->deptRepo->getByCompany($this->authUser()->company_id);
    }

    public function getLeaveTypes(): EloquentCollection
    {
        return $this->leaveTypeRepo->getByCompany($this->authUser()->company_id);
    }

    public function render(): View
    {
        return view('livewire.admin.report.leave', [
            'rows'        => $this->getReport(),
            'departments' => $this->getDepartments(),
            'leaveTypes'  => $this->getLeaveTypes(),
        ]);
    }
}
