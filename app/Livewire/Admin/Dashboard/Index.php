<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Dashboard;

use App\Repositories\Contracts\AttendanceRepositoryInterface;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Repositories\Contracts\LeaveRequestRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Livewire\Admin\AdminComponent;

#[Layout('layouts.admin')]
#[Title('Dashboard')]
class Index extends AdminComponent
{
    public int $totalEmployees  = 0;
    public int $presentToday    = 0;
    public int $lateToday       = 0;
    public int $onLeaveToday    = 0;

    public Collection $pendingLeaves;
    public Collection $recentActivity;

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
        $this->userRepo = $userRepo;
        $this->attendanceRepo = $attendanceRepo;
        $this->leaveRepo = $leaveRepo;
        $this->auditRepo = $auditRepo;
    }

    public function mount(): void
    {
        $companyId = $this->authUser()->company_id;

        $this->totalEmployees = $this->userRepo->countActive($companyId);
        $this->presentToday   = $this->attendanceRepo->countPresentToday($companyId);
        $this->lateToday      = $this->attendanceRepo->countLateToday($companyId);
        $this->onLeaveToday   = $this->leaveRepo->countOnLeaveToday($companyId);

        $this->pendingLeaves  = $this->leaveRepo->getRecentPendingForApprover($this->authUser(), 5);
        $this->recentActivity = $this->auditRepo->getRecent($companyId, 10);
    }

    public function render(): View
    {
        return view('livewire.admin.dashboard.index');
    }
}
