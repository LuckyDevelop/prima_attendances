<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Attendance;

use App\Enums\AttendanceStatus;
use App\Enums\UserRole;
use App\Livewire\Admin\AdminComponent;
use App\Repositories\Contracts\AttendanceRepositoryInterface;
use App\Repositories\Contracts\DepartmentRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
#[Title('Daftar Absensi')]
class Index extends AdminComponent
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $departmentId = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $dateFrom = '';

    #[Url]
    public string $dateTo = '';

    #[Url]
    public bool $lateOnly = false;

    #[Url]
    public bool $mockOnly = false;

    public int $perPage = 15;

    protected AttendanceRepositoryInterface $attendanceRepo;
    protected DepartmentRepositoryInterface $deptRepo;

    public function boot(
        AttendanceRepositoryInterface $attendanceRepo,
        DepartmentRepositoryInterface $deptRepo,
    ): void {
        $this->attendanceRepo = $attendanceRepo;
        $this->deptRepo       = $deptRepo;
    }

    public function mount(): void
    {
        if (empty($this->dateFrom)) {
            $this->dateFrom = today()->toDateString();
        }
        if (empty($this->dateTo)) {
            $this->dateTo = today()->toDateString();
        }
    }

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedDepartmentId(): void { $this->resetPage(); }
    public function updatedStatus(): void { $this->resetPage(); }
    public function updatedDateFrom(): void { $this->resetPage(); }
    public function updatedDateTo(): void { $this->resetPage(); }
    public function updatedLateOnly(): void { $this->resetPage(); }
    public function updatedMockOnly(): void { $this->resetPage(); }

    public function resetFilters(): void
    {
        $this->reset(['search', 'departmentId', 'status', 'lateOnly', 'mockOnly']);
        $this->dateFrom = today()->toDateString();
        $this->dateTo   = today()->toDateString();
        $this->resetPage();
    }

    private function buildFilters(): array
    {
        $user    = $this->authUser();
        $filters = [
            'search'      => $this->search ?: null,
            'status'      => $this->status ?: null,
            'date_from'   => $this->dateFrom ?: null,
            'date_to'     => $this->dateTo ?: null,
            'late_only'   => $this->lateOnly,
            'mock_only'   => $this->mockOnly,
            'per_page'    => $this->perPage,
        ];

        if ($this->departmentId) {
            $filters['department_id'] = $this->departmentId;
        }

        if ($user->role === UserRole::MANAGER) {
            $filters['manager_id'] = $user->id;
        }

        return $filters;
    }

    public function getAttendances(): LengthAwarePaginator
    {
        return $this->attendanceRepo->paginateForAdmin(
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
        return view('livewire.admin.attendance.index', [
            'attendances' => $this->getAttendances(),
            'departments' => $this->getDepartments(),
            'statuses'    => AttendanceStatus::cases(),
        ]);
    }
}
