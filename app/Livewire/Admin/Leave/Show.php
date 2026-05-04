<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Leave;

use App\Enums\ApprovalDecision;
use App\Enums\ApprovalRequestType;
use App\Enums\LeaveStatus;
use App\Enums\UserRole;
use App\Livewire\Admin\AdminComponent;
use App\Models\LeaveRequest;
use App\Repositories\Contracts\ApprovalRepositoryInterface;
use App\Repositories\Contracts\LeaveBalanceRepositoryInterface;
use App\Repositories\Contracts\LeaveRequestRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

#[Layout('layouts.admin')]
#[Title('Detail Cuti')]
class Show extends AdminComponent
{
    public int $leaveId;
    public string $comment = '';
    public string $pendingAction = '';

    protected LeaveRequestRepositoryInterface $leaveRepo;
    protected ApprovalRepositoryInterface $approvalRepo;
    protected LeaveBalanceRepositoryInterface $balanceRepo;

    public function boot(
        LeaveRequestRepositoryInterface $leaveRepo,
        ApprovalRepositoryInterface $approvalRepo,
        LeaveBalanceRepositoryInterface $balanceRepo,
    ): void {
        $this->leaveRepo   = $leaveRepo;
        $this->approvalRepo = $approvalRepo;
        $this->balanceRepo  = $balanceRepo;
    }

    public function mount(int $id): void
    {
        $leave = $this->leaveRepo->find($id);

        abort_if(! $leave, 404);
        abort_if($leave->user?->company_id !== $this->authUser()->company_id, 403);

        $this->leaveId = $id;
    }

    public function getLeave(): ?LeaveRequest
    {
        return $this->leaveRepo->find($this->leaveId);
    }

    /** Check if current user can approve/reject this leave. */
    public function canAct(LeaveRequest $leave): bool
    {
        if ($leave->status !== LeaveStatus::PENDING) {
            return false;
        }

        $user = $this->authUser();

        if (in_array($user->role, [UserRole::HR, UserRole::ADMIN], true)) {
            return true;
        }

        if ($user->role === UserRole::MANAGER) {
            return $leave->user?->department?->manager_id === $user->id;
        }

        return false;
    }

    public function openApprove(): void
    {
        $this->comment       = '';
        $this->pendingAction = 'approve';
        $this->dispatch('open-modal', name: 'leave-action');
    }

    public function openReject(): void
    {
        $this->comment       = '';
        $this->pendingAction = 'reject';
        $this->dispatch('open-modal', name: 'leave-action');
    }

    public function confirmAction(): void
    {
        if ($this->pendingAction === 'reject') {
            $this->validate(['comment' => ['required', 'string', 'min:5', 'max:500']]);
        } else {
            $this->validate(['comment' => ['nullable', 'string', 'max:500']]);
        }

        $leave = $this->leaveRepo->find($this->leaveId);

        if (! $leave || ! $this->canAct($leave)) {
            $this->dispatch('toast', type: 'error', message: 'Tidak dapat memproses pengajuan ini.');
            $this->dispatch('close-modal', name: 'leave-action');
            return;
        }

        DB::beginTransaction();
        try {
            $decision = $this->pendingAction === 'approve'
                ? ApprovalDecision::APPROVED
                : ApprovalDecision::REJECTED;

            $newStatus = $this->pendingAction === 'approve'
                ? LeaveStatus::APPROVED
                : LeaveStatus::REJECTED;

            $this->approvalRepo->createApproval(
                ApprovalRequestType::LEAVE,
                $leave->id,
                $this->authUser(),
                $decision,
                $this->comment ?: null,
            );

            $this->leaveRepo->updateStatus($leave, $newStatus);

            if ($decision === ApprovalDecision::APPROVED && $leave->leaveType?->default_quota > 0) {
                $this->balanceRepo->deductBalance(
                    $leave->user_id,
                    $leave->leave_type_id,
                    (int) date('Y', strtotime($leave->start_date)),
                    $leave->total_days,
                );
            }

            DB::commit();

            $msg = $this->pendingAction === 'approve' ? 'Cuti berhasil disetujui.' : 'Cuti berhasil ditolak.';
            $this->dispatch('toast', type: 'success', message: $msg);
            $this->dispatch('close-modal', name: 'leave-action');
            $this->comment = '';
            $this->pendingAction = '';

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Leave approval failed', [
                'admin_id' => $this->authUser()->id,
                'leave_id' => $this->leaveId,
                'error'    => $e->getMessage(),
            ]);
            $this->dispatch('toast', type: 'error', message: 'Gagal memproses pengajuan. Silakan coba lagi.');
        }
    }

    public function render(): View
    {
        $leave = $this->getLeave();
        abort_if(! $leave, 404);

        return view('livewire.admin.leave.show', [
            'leave'  => $leave,
            'canAct' => $this->canAct($leave),
        ]);
    }
}
