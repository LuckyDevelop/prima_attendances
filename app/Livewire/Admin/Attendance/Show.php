<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Attendance;

use App\Enums\AttendanceStatus;
use App\Livewire\Admin\AdminComponent;
use App\Models\Attendance;
use App\Models\AuditLog;
use App\Repositories\Contracts\AttendanceRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

#[Layout('layouts.admin')]
#[Title('Detail Absensi')]
class Show extends AdminComponent
{
    public int $attendanceId;

    protected AttendanceRepositoryInterface $attendanceRepo;

    public function boot(AttendanceRepositoryInterface $attendanceRepo): void
    {
        $this->attendanceRepo = $attendanceRepo;
    }

    public function mount(int $id): void
    {
        $attendance = $this->attendanceRepo->findById($id);

        abort_if(! $attendance, 404);
        abort_if($attendance->user?->company_id !== $this->authUser()->company_id, 403);

        $this->attendanceId = $id;
    }

    public function getAttendance(): ?Attendance
    {
        return $this->attendanceRepo->findById($this->attendanceId);
    }

    public function getAuditLogs(): Collection
    {
        return AuditLog::where('entity', 'attendance')
            ->where('entity_id', (string) $this->attendanceId)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();
    }

    public function render(): View
    {
        $attendance = $this->getAttendance();
        abort_if(! $attendance, 404);

        return view('livewire.admin.attendance.show', [
            'attendance' => $attendance,
            'auditLogs'  => $this->getAuditLogs(),
        ]);
    }
}
