<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Permission;

use App\Enums\ApprovalDecision;
use App\Enums\ApprovalRequestType;
use App\Enums\LeaveStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Approval\ApprovalActionRequest;
use App\Http\Requests\Api\Permission\StorePermissionRequest;
use App\Http\Resources\Api\PermissionRequestResource;
use App\Models\AuditLog;
use App\Models\Department;
use App\Repositories\Contracts\ApprovalRepositoryInterface;
use App\Repositories\Contracts\PermissionRequestRepositoryInterface;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PermissionRequestController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly PermissionRequestRepositoryInterface $permissionRepository,
        private readonly ApprovalRepositoryInterface $approvalRepository,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = [
            'status'   => $request->query('status'),
            'type'     => $request->query('type'),
            'year'     => $request->query('year'),
            'per_page' => $request->query('per_page', 15),
        ];

        $paginator = $this->permissionRepository->paginateByUser($request->user()->id, $filters);
        $items     = PermissionRequestResource::collection($paginator->items())->collection;
        $paginator->setCollection($items);

        return $this->successPaginated($paginator, 'Data pengajuan izin berhasil diambil.');
    }

    public function store(StorePermissionRequest $request): JsonResponse
    {
        $user = $request->user();

        $alreadyExists = $this->permissionRepository->existsForDateAndType(
            $user->id,
            $request->request_date,
            $request->permission_type,
        );

        if ($alreadyExists) {
            return $this->validationError(
                [],
                'Anda sudah memiliki pengajuan izin dengan jenis yang sama pada tanggal tersebut.'
            );
        }

        DB::beginTransaction();
        try {
            $attachmentPath = null;
            if ($request->hasFile('attachment')) {
                $attachmentPath = $request->file('attachment')->store(
                    "attachments/{$user->id}/permission",
                    'public'
                );
            }

            $permission = $this->permissionRepository->create($user->id, array_merge(
                $request->validated(),
                ['attachment_path' => $attachmentPath]
            ));

            AuditLog::create([
                'user_id'    => $user->id,
                'action'     => 'create_permission_request',
                'entity'     => 'PermissionRequest',
                'entity_id'  => (string) $permission->id,
                'metadata'   => [
                    'permission_type' => $request->permission_type,
                    'request_date'    => $request->request_date,
                ],
                'ip_address' => $request->ip(),
            ]);

            DB::commit();

            return $this->success(
                new PermissionRequestResource($permission),
                'Pengajuan izin berhasil dibuat.',
                201
            );

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to create permission request', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);

            return $this->serverError();
        }
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $permission = $this->permissionRepository->find($id);

        if ($permission === null) {
            return $this->notFound('Pengajuan izin tidak ditemukan.');
        }

        if ($permission->user_id !== $request->user()->id) {
            return $this->forbidden();
        }

        return $this->success(
            new PermissionRequestResource($permission),
            'Detail pengajuan izin berhasil diambil.'
        );
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $permission = $this->permissionRepository->find($id);

        if ($permission === null) {
            return $this->notFound('Pengajuan izin tidak ditemukan.');
        }

        if ($permission->user_id !== $request->user()->id) {
            return $this->forbidden();
        }

        if ($permission->status !== LeaveStatus::PENDING) {
            return $this->validationError([], 'Hanya pengajuan izin dengan status pending yang dapat dibatalkan.');
        }

        DB::beginTransaction();
        try {
            $this->permissionRepository->cancel($permission);

            AuditLog::create([
                'user_id'    => $request->user()->id,
                'action'     => 'cancel_permission_request',
                'entity'     => 'PermissionRequest',
                'entity_id'  => (string) $id,
                'ip_address' => $request->ip(),
            ]);

            DB::commit();

            return $this->success(null, 'Pengajuan izin berhasil dibatalkan.');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to cancel permission request', [
                'user_id'       => $request->user()->id,
                'permission_id' => $id,
                'error'         => $e->getMessage(),
            ]);

            return $this->serverError();
        }
    }

    public function approve(ApprovalActionRequest $request, int $id): JsonResponse
    {
        $approver   = $request->user();
        $permission = $this->permissionRepository->findForApproval($id);

        if ($permission === null) {
            return $this->notFound('Pengajuan izin tidak ditemukan.');
        }

        if ($permission->status !== LeaveStatus::PENDING) {
            return $this->validationError([], 'Pengajuan izin ini sudah diproses sebelumnya.');
        }

        if (!$this->canActOn($approver, $permission->user)) {
            return $this->forbidden('Anda tidak memiliki akses untuk menyetujui pengajuan ini.');
        }

        DB::beginTransaction();
        try {
            $this->permissionRepository->updateStatus($permission, LeaveStatus::APPROVED);

            $this->approvalRepository->createApproval(
                ApprovalRequestType::PERMISSION,
                $permission->id,
                $approver,
                ApprovalDecision::APPROVED,
                $request->input('comment')
            );

            AuditLog::create([
                'user_id'    => $approver->id,
                'action'     => 'approve_permission_request',
                'entity'     => 'PermissionRequest',
                'entity_id'  => (string) $permission->id,
                'metadata'   => ['target_user_id' => $permission->user_id],
                'ip_address' => $request->ip(),
            ]);

            DB::commit();

            return $this->success(
                new PermissionRequestResource($permission),
                'Pengajuan izin berhasil disetujui.'
            );

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to approve permission request', [
                'approver_id'   => $approver->id,
                'permission_id' => $id,
                'error'         => $e->getMessage(),
            ]);

            return $this->serverError();
        }
    }

    public function reject(ApprovalActionRequest $request, int $id): JsonResponse
    {
        $approver   = $request->user();
        $permission = $this->permissionRepository->findForApproval($id);

        if ($permission === null) {
            return $this->notFound('Pengajuan izin tidak ditemukan.');
        }

        if ($permission->status !== LeaveStatus::PENDING) {
            return $this->validationError([], 'Pengajuan izin ini sudah diproses sebelumnya.');
        }

        if (!$this->canActOn($approver, $permission->user)) {
            return $this->forbidden('Anda tidak memiliki akses untuk menolak pengajuan ini.');
        }

        DB::beginTransaction();
        try {
            $this->permissionRepository->updateStatus($permission, LeaveStatus::REJECTED);

            $this->approvalRepository->createApproval(
                ApprovalRequestType::PERMISSION,
                $permission->id,
                $approver,
                ApprovalDecision::REJECTED,
                $request->input('comment')
            );

            AuditLog::create([
                'user_id'    => $approver->id,
                'action'     => 'reject_permission_request',
                'entity'     => 'PermissionRequest',
                'entity_id'  => (string) $permission->id,
                'metadata'   => ['target_user_id' => $permission->user_id],
                'ip_address' => $request->ip(),
            ]);

            DB::commit();

            return $this->success(
                new PermissionRequestResource($permission),
                'Pengajuan izin berhasil ditolak.'
            );

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to reject permission request', [
                'approver_id'   => $approver->id,
                'permission_id' => $id,
                'error'         => $e->getMessage(),
            ]);

            return $this->serverError();
        }
    }

    /**
     * Check whether the approver has authority over the target user.
     * Manager: only users in departments they manage.
     * HR/Admin: all users in the same company.
     */
    private function canActOn(\App\Models\User $approver, \App\Models\User $target): bool
    {
        if ($approver->role === UserRole::MANAGER) {
            $managedDeptIds = Department::where('manager_id', $approver->id)
                ->where('company_id', $approver->company_id)
                ->pluck('id');

            return $managedDeptIds->contains($target->department_id);
        }

        return $approver->company_id === $target->company_id;
    }
}
