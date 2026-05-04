<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Approval;

use App\Enums\ApprovalDecision;
use App\Enums\ApprovalRequestType;
use App\Enums\LeaveStatus;
use App\Livewire\Admin\AdminComponent;
use App\Repositories\Contracts\ApprovalRepositoryInterface;
use App\Repositories\Contracts\DepartmentRepositoryInterface;
use App\Repositories\Contracts\LeaveBalanceRepositoryInterface;
use App\Repositories\Contracts\LeaveRequestRepositoryInterface;
use App\Repositories\Contracts\PermissionRequestRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
#[Title('Approval Center')]
class Index extends AdminComponent
{
    use WithPagination;

    #[Url]
    public string $tab = 'pending';

    #[Url]
    public string $requestType = 'leave';

    #[Url]
    public string $departmentId = '';

    public string $comment = '';
    public int $actingId = 0;
    public string $actingType = 'leave';
    public string $pendingAction = '';

    /** IDs selected for bulk action. */
    public array $selected = [];

    public int $perPage = 15;

    protected ApprovalRepositoryInterface $approvalRepo;
    protected LeaveRequestRepositoryInterface $leaveRepo;
    protected PermissionRequestRepositoryInterface $permRepo;
    protected LeaveBalanceRepositoryInterface $balanceRepo;
    protected DepartmentRepositoryInterface $deptRepo;

    public function boot(
        ApprovalRepositoryInterface $approvalRepo,
        LeaveRequestRepositoryInterface $leaveRepo,
        PermissionRequestRepositoryInterface $permRepo,
        LeaveBalanceRepositoryInterface $balanceRepo,
        DepartmentRepositoryInterface $deptRepo,
    ): void {
        $this->approvalRepo = $approvalRepo;
        $this->leaveRepo    = $leaveRepo;
        $this->permRepo     = $permRepo;
        $this->balanceRepo  = $balanceRepo;
        $this->deptRepo     = $deptRepo;
    }

    public function updatedTab(): void
    {
        $this->selected = [];
        $this->resetPage();
    }

    public function updatedRequestType(): void
    {
        $this->selected = [];
        $this->resetPage();
    }

    public function updatedDepartmentId(): void
    {
        $this->selected = [];
        $this->resetPage();
    }

    public function getRequests(): LengthAwarePaginator
    {
        return $this->approvalRepo->paginateForAdmin($this->authUser(), [
            'status'       => $this->tab,
            'request_type' => $this->requestType,
            'department_id'=> $this->departmentId ?: null,
            'per_page'     => $this->perPage,
        ]);
    }

    public function getDepartments(): Collection
    {
        return $this->deptRepo->getByCompany($this->authUser()->company_id);
    }

    public function openAction(int $id, string $type, string $action): void
    {
        $this->actingId      = $id;
        $this->actingType    = $type;
        $this->pendingAction = $action;
        $this->comment       = '';
        $this->dispatch('open-modal', name: 'approval-action');
    }

    public function confirmAction(): void
    {
        if ($this->pendingAction === 'reject') {
            $this->validate(['comment' => ['required', 'string', 'min:5', 'max:500']]);
        } else {
            $this->validate(['comment' => ['nullable', 'string', 'max:500']]);
        }

        $decision  = $this->pendingAction === 'approve' ? ApprovalDecision::APPROVED : ApprovalDecision::REJECTED;
        $newStatus = $this->pendingAction === 'approve' ? LeaveStatus::APPROVED : LeaveStatus::REJECTED;

        DB::beginTransaction();
        try {
            if ($this->actingType === 'leave') {
                $request = $this->leaveRepo->find($this->actingId);
                $requestType = ApprovalRequestType::LEAVE;
            } else {
                $request = $this->permRepo->find($this->actingId);
                $requestType = ApprovalRequestType::PERMISSION;
            }

            if (! $request) {
                $this->dispatch('toast', type: 'error', message: 'Data tidak ditemukan.');
                $this->dispatch('close-modal', name: 'approval-action');
                DB::rollBack();
                return;
            }

            $this->approvalRepo->createApproval(
                $requestType,
                $request->id,
                $this->authUser(),
                $decision,
                $this->comment ?: null,
            );

            if ($this->actingType === 'leave') {
                $this->leaveRepo->updateStatus($request, $newStatus);
                if ($decision === ApprovalDecision::APPROVED && $request->leaveType?->default_quota > 0) {
                    $this->balanceRepo->deductBalance(
                        $request->user_id,
                        $request->leave_type_id,
                        (int) date('Y', strtotime($request->start_date)),
                        $request->total_days,
                    );
                }
            } else {
                $this->permRepo->updateStatus($request, $newStatus);
            }

            DB::commit();

            $msg = $this->pendingAction === 'approve' ? 'Pengajuan berhasil disetujui.' : 'Pengajuan berhasil ditolak.';
            $this->dispatch('toast', type: 'success', message: $msg);
            $this->dispatch('close-modal', name: 'approval-action');
            $this->selected = [];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Approval action failed', [
                'admin_id' => $this->authUser()->id,
                'error'    => $e->getMessage(),
            ]);
            $this->dispatch('toast', type: 'error', message: 'Gagal memproses. Silakan coba lagi.');
        }
    }

    public function bulkApprove(): void
    {
        if (empty($this->selected)) {
            $this->dispatch('toast', type: 'error', message: 'Pilih minimal satu pengajuan.');
            return;
        }

        DB::beginTransaction();
        try {
            $count = 0;
            foreach ($this->selected as $item) {
                [$id, $type] = explode(':', $item);
                $id = (int) $id;

                if ($type === 'leave') {
                    $request = $this->leaveRepo->find($id);
                    if (! $request) continue;

                    $this->approvalRepo->createApproval(
                        ApprovalRequestType::LEAVE, $id, $this->authUser(),
                        ApprovalDecision::APPROVED, null,
                    );
                    $this->leaveRepo->updateStatus($request, LeaveStatus::APPROVED);

                    if ($request->leaveType?->default_quota > 0) {
                        $this->balanceRepo->deductBalance(
                            $request->user_id, $request->leave_type_id,
                            (int) date('Y', strtotime($request->start_date)),
                            $request->total_days,
                        );
                    }
                } else {
                    $request = $this->permRepo->find($id);
                    if (! $request) continue;

                    $this->approvalRepo->createApproval(
                        ApprovalRequestType::PERMISSION, $id, $this->authUser(),
                        ApprovalDecision::APPROVED, null,
                    );
                    $this->permRepo->updateStatus($request, LeaveStatus::APPROVED);
                }
                $count++;
            }

            DB::commit();
            $this->selected = [];
            $this->dispatch('toast', type: 'success', message: "{$count} pengajuan berhasil disetujui.");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk approval failed', ['admin_id' => $this->authUser()->id, 'error' => $e->getMessage()]);
            $this->dispatch('toast', type: 'error', message: 'Gagal memproses bulk approval.');
        }
    }

    public function render(): View
    {
        return view('livewire.admin.approval.index', [
            'requests'    => $this->getRequests(),
            'departments' => $this->getDepartments(),
        ]);
    }
}
