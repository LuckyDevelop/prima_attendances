<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Attendance;

use App\Livewire\Admin\AdminComponent;
use App\Repositories\Contracts\AttendanceRepositoryInterface;
use App\Repositories\Contracts\DepartmentRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;

#[Layout('layouts.admin')]
#[Title('Kalender Absensi')]
class Calendar extends AdminComponent
{
    #[Url]
    public int $year = 0;

    #[Url]
    public int $month = 0;

    #[Url]
    public string $departmentId = '';

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
        if ($this->year === 0)  { $this->year  = (int) now()->format('Y'); }
        if ($this->month === 0) { $this->month = (int) now()->format('m'); }
    }

    public function prevMonth(): void
    {
        $date        = Carbon::create($this->year, $this->month, 1)->subMonth();
        $this->year  = $date->year;
        $this->month = $date->month;
    }

    public function nextMonth(): void
    {
        $date        = Carbon::create($this->year, $this->month, 1)->addMonth();
        $this->year  = $date->year;
        $this->month = $date->month;
    }

    public function goToToday(): void
    {
        $this->year  = (int) now()->format('Y');
        $this->month = (int) now()->format('m');
    }

    public function getDepartments(): Collection
    {
        return $this->deptRepo->getByCompany($this->authUser()->company_id);
    }

    /** Keyed by 'Y-m-d' → ['present', 'absent', 'leave', 'total']. */
    private function buildSummaryMap(): array
    {
        $rows = $this->attendanceRepo->getMonthSummary(
            $this->authUser()->company_id,
            $this->year,
            $this->month,
            $this->departmentId ? (int) $this->departmentId : null,
        );

        $map = [];
        foreach ($rows as $row) {
            $key       = Carbon::parse($row->work_date)->format('Y-m-d');
            $map[$key] = [
                'present' => (int) $row->present,
                'absent'  => (int) $row->absent,
                'leave'   => (int) $row->leave,
                'total'   => (int) $row->total,
            ];
        }

        return $map;
    }

    public function render(): View
    {
        $firstDay    = Carbon::create($this->year, $this->month, 1);
        $daysInMonth = $firstDay->daysInMonth;
        $startDow    = ($firstDay->dayOfWeek + 6) % 7; // Mon=0 … Sun=6

        // Build calendar cell array (null = padding, int = day-of-month)
        $cells = array_merge(
            array_fill(0, $startDow, null),
            range(1, $daysInMonth),
        );
        while (count($cells) % 7 !== 0) {
            $cells[] = null;
        }

        return view('livewire.admin.attendance.calendar', [
            'firstDay'    => $firstDay,
            'weeks'       => array_chunk($cells, 7),
            'summaryMap'  => $this->buildSummaryMap(),
            'departments' => $this->getDepartments(),
        ]);
    }
}
