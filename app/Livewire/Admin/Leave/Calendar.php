<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Leave;

use App\Enums\LeaveStatus;
use App\Livewire\Admin\AdminComponent;
use App\Models\LeaveRequest;
use App\Repositories\Contracts\DepartmentRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;

#[Layout('layouts.admin')]
#[Title('Kalender Cuti')]
class Calendar extends AdminComponent
{
    #[Url]
    public int $year;

    #[Url]
    public int $month;

    #[Url]
    public string $departmentId = '';

    protected DepartmentRepositoryInterface $deptRepo;

    public function boot(DepartmentRepositoryInterface $deptRepo): void
    {
        $this->deptRepo = $deptRepo;
    }

    public function mount(): void
    {
        $this->year  = (int) now()->format('Y');
        $this->month = (int) now()->format('m');
    }

    public function prevMonth(): void
    {
        $date = \Carbon\Carbon::create($this->year, $this->month, 1)->subMonth();
        $this->year  = $date->year;
        $this->month = $date->month;
    }

    public function nextMonth(): void
    {
        $date = \Carbon\Carbon::create($this->year, $this->month, 1)->addMonth();
        $this->year  = $date->year;
        $this->month = $date->month;
    }

    public function getEvents(): array
    {
        $companyId = $this->authUser()->company_id;
        $startDate = \Carbon\Carbon::create($this->year, $this->month, 1)->startOfMonth()->toDateString();
        $endDate   = \Carbon\Carbon::create($this->year, $this->month, 1)->endOfMonth()->toDateString();

        $query = LeaveRequest::query()
            ->with(['user.department'])
            ->join('users', 'leave_requests.user_id', '=', 'users.id')
            ->where('users.company_id', $companyId)
            ->where('leave_requests.status', LeaveStatus::APPROVED)
            ->where('leave_requests.start_date', '<=', $endDate)
            ->where('leave_requests.end_date', '>=', $startDate)
            ->select('leave_requests.*');

        if ($this->departmentId) {
            $query->where('users.department_id', $this->departmentId);
        }

        return $query->get()->map(function (LeaveRequest $leave) {
            return [
                'id'         => $leave->id,
                'name'       => $leave->user?->full_name ?? '?',
                'start_date' => $leave->start_date,
                'end_date'   => $leave->end_date,
                'department' => $leave->user?->department?->name ?? '',
            ];
        })->toArray();
    }

    public function getDepartments(): Collection
    {
        return $this->deptRepo->getByCompany($this->authUser()->company_id);
    }

    public function render(): View
    {
        $firstDay = \Carbon\Carbon::create($this->year, $this->month, 1);
        $daysInMonth = $firstDay->daysInMonth;
        $startDow = $firstDay->dayOfWeek; // 0=Sun, adjust to Mon=0

        return view('livewire.admin.leave.calendar', [
            'events'      => $this->getEvents(),
            'departments' => $this->getDepartments(),
            'firstDay'    => $firstDay,
            'daysInMonth' => $daysInMonth,
            'startDow'    => ($startDow + 6) % 7, // Mon=0 … Sun=6
        ]);
    }
}
