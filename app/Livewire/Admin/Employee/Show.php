<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Employee;

use App\Models\User;
use App\Repositories\Contracts\AttendanceRepositoryInterface;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Repositories\Contracts\LeaveRequestRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use App\Livewire\Admin\AdminComponent;

#[Layout('layouts.admin')]
class Show extends AdminComponent
{
    public int $userId;
    public string $activeTab = 'info';

    public User $employee;

    protected UserRepositoryInterface $userRepo;
    protected AttendanceRepositoryInterface $attendanceRepo;
    protected LeaveRequestRepositoryInterface $leaveRepo;
    protected AuditLogRepositoryInterface $auditRepo;

    public function boot(
        UserRepositoryInterface $userRepo,
        AttendanceRepositoryInterface $attendanceRepo,
        LeaveRequestRepositoryInterface $leaveRepo,
        AuditLogRepositoryInterface $auditRepo,
    ): void {
        $this->userRepo       = $userRepo;
        $this->attendanceRepo = $attendanceRepo;
        $this->leaveRepo      = $leaveRepo;
        $this->auditRepo      = $auditRepo;
    }

    public function mount(int $userId): void
    {
        $this->userId   = $userId;
        $this->employee = $this->userRepo->findOrFail($userId);

        abort_if(
            $this->employee->company_id !== $this->authUser()->company_id,
            403,
        );
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function revokeDevice(int $deviceId): void
    {
        $device = $this->employee->devices()->findOrFail($deviceId);
        $device->update(['is_active' => false]);

        $this->dispatch('toast', type: 'success', message: 'Device berhasil dinonaktifkan.');
    }

    public function getAttendances(): \Illuminate\Pagination\LengthAwarePaginator
    {
        return $this->employee->attendances()
            ->orderByDesc('work_date')
            ->paginate(10, pageName: 'att_page');
    }

    public function getLeaves(): \Illuminate\Pagination\LengthAwarePaginator
    {
        return $this->employee->leaveRequests()
            ->with('leaveType:id,name')
            ->orderByDesc('submitted_at')
            ->paginate(10, pageName: 'leave_page');
    }

    public function getDevices(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->employee->devices()
            ->orderByDesc('last_login')
            ->get();
    }

    public function getAuditLogs(): \Illuminate\Pagination\LengthAwarePaginator
    {
        return $this->employee->auditLogs()
            ->orderByDesc('created_at')
            ->paginate(10, pageName: 'audit_page');
    }

    public function render(): View
    {
        $data = ['employee' => $this->employee];

        if ($this->activeTab === 'attendance') {
            $data['attendances'] = $this->getAttendances();
        } elseif ($this->activeTab === 'leaves') {
            $data['leaves'] = $this->getLeaves();
        } elseif ($this->activeTab === 'devices') {
            $data['devices'] = $this->getDevices();
        } elseif ($this->activeTab === 'audit') {
            $data['auditLogs'] = $this->getAuditLogs();
        }

        return view('livewire.admin.employee.show', $data)
            ->title('Detail: ' . $this->employee->full_name);
    }
}
