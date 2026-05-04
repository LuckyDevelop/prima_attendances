<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Report;

use App\Livewire\Admin\AdminComponent;
use App\Repositories\Contracts\AttendanceRepositoryInterface;
use App\Repositories\Contracts\DepartmentRepositoryInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;

#[Layout('layouts.admin')]
#[Title('Laporan Keterlambatan')]
class LateArrivals extends AdminComponent
{
    #[Url]
    public string $dateFrom = '';

    #[Url]
    public string $dateTo = '';

    #[Url]
    public string $departmentId = '';

    #[Url]
    public string $search = '';

    #[Url]
    public int $minLateMinutes = 0;

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
            $this->dateFrom = today()->startOfMonth()->toDateString();
        }
        if (empty($this->dateTo)) {
            $this->dateTo = today()->toDateString();
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['departmentId', 'search', 'minLateMinutes']);
        $this->dateFrom = today()->startOfMonth()->toDateString();
        $this->dateTo   = today()->toDateString();
    }

    private function buildFilters(): array
    {
        return [
            'date_from'        => $this->dateFrom ?: null,
            'date_to'          => $this->dateTo ?: null,
            'department_id'    => $this->departmentId ?: null,
            'search'           => $this->search ?: null,
            'min_late_minutes' => $this->minLateMinutes > 0 ? $this->minLateMinutes : null,
        ];
    }

    public function getReport(): Collection
    {
        return $this->attendanceRepo->getLateArrivalsReport(
            $this->authUser()->company_id,
            $this->buildFilters()
        );
    }

    public function getDepartments(): EloquentCollection
    {
        return $this->deptRepo->getByCompany($this->authUser()->company_id);
    }

    public function render(): View
    {
        return view('livewire.admin.report.late-arrivals', [
            'rows'        => $this->getReport(),
            'departments' => $this->getDepartments(),
        ]);
    }
}
