<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Permission;

use App\Enums\ApprovalDecision;
use App\Enums\ApprovalRequestType;
use App\Enums\LeaveStatus;
use App\Enums\UserRole;
use App\Livewire\Admin\AdminComponent;
use App\Models\PermissionRequest;
use App\Repositories\Contracts\ApprovalRepositoryInterface;
use App\Repositories\Contracts\PermissionRequestRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

#[Layout('layouts.admin')]
#[Title('Detail Izin')]
class Show extends AdminComponent
{
    public int $permissionId;
    public string $comment = '';
    public string $pendingAction = '';

    protected PermissionRequestRepositoryInterface $permRepo;
    protected ApprovalRepositoryInterface $approvalRepo;

    public function boot(
        PermissionRequestRepositoryInterface $permRepo,
        ApprovalRepositoryInterface $approvalRepo,
    ): void {
        $this->permRepo     = $permRepo;
        $this->approvalRepo = $approvalRepo;
    }

    public function mount(int $id): void
    {
        $perm = $this->permRepo->find($id);

        abort_if(! $perm, 404);
        abort_if($perm->user?->company_id !== $this->authUser()->company_id, 403);

        $this->permissionId = $id;
    }

    public function getPermission(): ?PermissionRequest
    {
        return $this->permRepo->find($this->permissionId);
    }

    public function canAct(PermissionRequest $perm): bool
    {
        $sv = is_object($perm->status) ? $perm->status->value : $perm->status;
        if ($sv !== 'pending') {
            return false;
        }

        $user = $this->authUser();

        if (in_array($user->role, [UserRole::HR, UserRole::ADMIN], true)) {
            return true;
        }

        if ($user->role === UserRole::MANAGER) {
            return $perm->user?->department?->manager_id === $user->id;
        }

        return false;
    }

    public function openApprove(): void
    {
        $this->comment       = '';
        $this->pendingAction = 'approve';
        $this->dispatch('open-modal', name: 'perm-action');
    }

    public function openReject(): void
    {
        $this->comment       = '';
        $this->pendingAction = 'reject';
        $this->dispatch('open-modal', name: 'perm-action');
    }

    public function confirmAction(): void
    {
        if ($this->pendingAction === 'reject') {
            $this->validate(['comment' => ['required', 'string', 'min:5', 'max:500']]);
        } else {
            $this->validate(['comment' => ['nullable', 'string', 'max:500']]);
        }

        $perm = $this->permRepo->find($this->permissionId);

        if (! $perm || ! $this->canAct($perm)) {
            $this->dispatch('toast', type: 'error', message: 'Tidak dapat memproses pengajuan ini.');
            $this->dispatch('close-modal', name: 'perm-action');
            return;
        }

        DB::beginTransaction();
        try {
            $decision  = $this->pendingAction === 'approve' ? ApprovalDecision::APPROVED : ApprovalDecision::REJECTED;
            $newStatus = $this->pendingAction === 'approve' ? LeaveStatus::APPROVED : LeaveStatus::REJECTED;

            $this->approvalRepo->createApproval(
                ApprovalRequestType::PERMISSION,
                $perm->id,
                $this->authUser(),
                $decision,
                $this->comment ?: null,
            );

            $this->permRepo->updateStatus($perm, $newStatus);

            DB::commit();

            $msg = $this->pendingAction === 'approve' ? 'Izin berhasil disetujui.' : 'Izin berhasil ditolak.';
            $this->dispatch('toast', type: 'success', message: $msg);
            $this->dispatch('close-modal', name: 'perm-action');
            $this->comment = '';
            $this->pendingAction = '';

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Permission approval failed', [
                'admin_id'      => $this->authUser()->id,
                'permission_id' => $this->permissionId,
                'error'         => $e->getMessage(),
            ]);
            $this->dispatch('toast', type: 'error', message: 'Gagal memproses pengajuan. Silakan coba lagi.');
        }
    }

    public function render(): View
    {
        $perm = $this->getPermission();
        abort_if(! $perm, 404);

        return view('livewire.admin.permission.show', [
            'perm'   => $perm,
            'canAct' => $this->canAct($perm),
        ]);
    }
}
