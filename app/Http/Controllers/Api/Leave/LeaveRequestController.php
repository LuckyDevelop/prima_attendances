<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Leave;

use App\Enums\ApprovalDecision;
use App\Enums\ApprovalRequestType;
use App\Enums\LeaveStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Approval\ApprovalActionRequest;
use App\Http\Requests\Api\Leave\StoreLeaveRequest;
use App\Http\Resources\Api\LeaveBalanceResource;
use App\Http\Resources\Api\LeaveRequestResource;
use App\Http\Resources\Api\LeaveTypeResource;
use App\Models\AuditLog;
use App\Models\Department;
use App\Repositories\Contracts\ApprovalRepositoryInterface;
use App\Repositories\Contracts\LeaveBalanceRepositoryInterface;
use App\Repositories\Contracts\LeaveRequestRepositoryInterface;
use App\Repositories\Contracts\LeaveTypeRepositoryInterface;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LeaveRequestController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly LeaveRequestRepositoryInterface $leaveRequestRepository,
        private readonly LeaveTypeRepositoryInterface $leaveTypeRepository,
        private readonly LeaveBalanceRepositoryInterface $leaveBalanceRepository,
        private readonly ApprovalRepositoryInterface $approvalRepository,
    ) {}

    public function leaveTypes(Request $request): JsonResponse
    {
        $types = $this->leaveTypeRepository->getByCompany($request->user()->company_id);

        return $this->success(
            LeaveTypeResource::collection($types),
            'Jenis cuti berhasil diambil.'
        );
    }

    public function leaveBalances(Request $request): JsonResponse
    {
        $balances = $this->leaveBalanceRepository->getByUserAndYear(
            $request->user()->id,
            now()->year
        );

        return $this->success(
            LeaveBalanceResource::collection($balances),
            'Saldo cuti berhasil diambil.'
        );
    }

    public function index(Request $request): JsonResponse
    {
        $filters = [
            'status'   => $request->query('status'),
            'year'     => $request->query('year'),
            'per_page' => $request->query('per_page', 15),
        ];

        $paginator = $this->leaveRequestRepository->paginateByUser($request->user()->id, $filters);
        $items     = LeaveRequestResource::collection($paginator->items())->collection;
        $paginator->setCollection($items);

        return $this->successPaginated($paginator, 'Data pengajuan cuti berhasil diambil.');
    }

    public function store(StoreLeaveRequest $request): JsonResponse
    {
        $user = $request->user();
        $leaveType = $this->leaveTypeRepository->find((int) $request->leave_type_id);
        if ($leaveType === null || $leaveType->company_id !== $user->company_id) {
            return $this->notFound('Jenis cuti tidak ditemukan.');
        }

        if ($leaveType->requires_attachment && !$request->hasFile('attachment')) {
            return $this->validationError(
                ['attachment' => ['Jenis cuti ini memerlukan lampiran.']],
                'Data tidak valid.'
            );
        }

        $totalDays = $this->countWorkingDays(
            Carbon::parse($request->start_date),
            Carbon::parse($request->end_date)
        );

        if ($totalDays === 0) {
            return $this->validationError([], 'Tidak ada hari kerja dalam rentang tanggal yang dipilih.');
        }

        if ($leaveType->default_quota > 0) {
            $balance = $this->leaveBalanceRepository->findByUserLeaveTypeAndYear(
                $user->id,
                $leaveType->id,
                now()->year
            );

            if ($balance === null || $balance->remaining < $totalDays) {
                return $this->validationError(
                    [],
                    sprintf(
                        'Saldo cuti tidak mencukupi. Sisa saldo: %d hari, dibutuhkan: %d hari.',
                        $balance?->remaining ?? 0,
                        $totalDays
                    )
                );
            }
        }

        DB::beginTransaction();
        try {
            $attachmentPath = null;
            if ($request->hasFile('attachment')) {
                $attachmentPath = $request->file('attachment')->store(
                    "attachments/{$user->id}/leave",
                    'public'
                );
            }

            $leave = $this->leaveRequestRepository->create($user->id, array_merge(
                $request->validated(),
                [
                    'total_days'      => $totalDays,
                    'attachment_path' => $attachmentPath,
                ]
            ));

            AuditLog::create([
                'user_id'    => $user->id,
                'action'     => 'create_leave_request',
                'entity'     => 'LeaveRequest',
                'entity_id'  => (string) $leave->id,
                'metadata'   => ['leave_type_id' => $leaveType->id, 'total_days' => $totalDays],
                'ip_address' => $request->ip(),
            ]);

            DB::commit();

            $leave->load('leaveType');

            return $this->success(new LeaveRequestResource($leave), 'Pengajuan cuti berhasil dibuat.', 201);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to create leave request', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);

            return $this->serverError();
        }
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $leave = $this->leaveRequestRepository->find($id);

        if ($leave === null) {
            return $this->notFound('Pengajuan cuti tidak ditemukan.');
        }

        if ($leave->user_id !== $request->user()->id) {
            return $this->forbidden();
        }

        return $this->success(new LeaveRequestResource($leave), 'Detail pengajuan cuti berhasil diambil.');
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $leave = $this->leaveRequestRepository->find($id);

        if ($leave === null) {
            return $this->notFound('Pengajuan cuti tidak ditemukan.');
        }

        if ($leave->user_id !== $request->user()->id) {
            return $this->forbidden();
        }

        if ($leave->status !== LeaveStatus::PENDING) {
            return $this->validationError([], 'Hanya pengajuan cuti dengan status pending yang dapat dibatalkan.');
        }

        DB::beginTransaction();
        try {
            $this->leaveRequestRepository->cancel($leave);

            AuditLog::create([
                'user_id'    => $request->user()->id,
                'action'     => 'cancel_leave_request',
                'entity'     => 'LeaveRequest',
                'entity_id'  => (string) $id,
                'ip_address' => $request->ip(),
            ]);

            DB::commit();

            return $this->success(null, 'Pengajuan cuti berhasil dibatalkan.');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to cancel leave request', [
                'user_id'  => $request->user()->id,
                'leave_id' => $id,
                'error'    => $e->getMessage(),
            ]);

            return $this->serverError();
        }
    }

    public function approve(ApprovalActionRequest $request, int $id): JsonResponse
    {
        $approver = $request->user();
        $leave    = $this->leaveRequestRepository->findForApproval($id);

        if ($leave === null) {
            return $this->notFound('Pengajuan cuti tidak ditemukan.');
        }

        if ($leave->status !== LeaveStatus::PENDING) {
            return $this->validationError([], 'Pengajuan cuti ini sudah diproses sebelumnya.');
        }

        if (!$this->canActOn($approver, $leave->user)) {
            return $this->forbidden('Anda tidak memiliki akses untuk menyetujui pengajuan ini.');
        }

        DB::beginTransaction();
        try {
            $this->leaveRequestRepository->updateStatus($leave, LeaveStatus::APPROVED);

            $this->approvalRepository->createApproval(
                ApprovalRequestType::LEAVE,
                $leave->id,
                $approver,
                ApprovalDecision::APPROVED,
                $request->input('comment')
            );

            if ($leave->leaveType->default_quota > 0) {
                $this->leaveBalanceRepository->deductBalance(
                    $leave->user_id,
                    $leave->leave_type_id,
                    $leave->start_date->year,
                    $leave->total_days
                );
            }

            AuditLog::create([
                'user_id'    => $approver->id,
                'action'     => 'approve_leave_request',
                'entity'     => 'LeaveRequest',
                'entity_id'  => (string) $leave->id,
                'metadata'   => ['target_user_id' => $leave->user_id],
                'ip_address' => $request->ip(),
            ]);

            DB::commit();

            return $this->success(new LeaveRequestResource($leave), 'Pengajuan cuti berhasil disetujui.');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to approve leave request', [
                'approver_id' => $approver->id,
                'leave_id'    => $id,
                'error'       => $e->getMessage(),
            ]);

            return $this->serverError();
        }
    }

    public function reject(ApprovalActionRequest $request, int $id): JsonResponse
    {
        $approver = $request->user();
        $leave    = $this->leaveRequestRepository->findForApproval($id);

        if ($leave === null) {
            return $this->notFound('Pengajuan cuti tidak ditemukan.');
        }

        if ($leave->status !== LeaveStatus::PENDING) {
            return $this->validationError([], 'Pengajuan cuti ini sudah diproses sebelumnya.');
        }

        if (!$this->canActOn($approver, $leave->user)) {
            return $this->forbidden('Anda tidak memiliki akses untuk menolak pengajuan ini.');
        }

        DB::beginTransaction();
        try {
            $this->leaveRequestRepository->updateStatus($leave, LeaveStatus::REJECTED);

            $this->approvalRepository->createApproval(
                ApprovalRequestType::LEAVE,
                $leave->id,
                $approver,
                ApprovalDecision::REJECTED,
                $request->input('comment')
            );

            AuditLog::create([
                'user_id'    => $approver->id,
                'action'     => 'reject_leave_request',
                'entity'     => 'LeaveRequest',
                'entity_id'  => (string) $leave->id,
                'metadata'   => ['target_user_id' => $leave->user_id],
                'ip_address' => $request->ip(),
            ]);

            DB::commit();

            return $this->success(new LeaveRequestResource($leave), 'Pengajuan cuti berhasil ditolak.');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to reject leave request', [
                'approver_id' => $approver->id,
                'leave_id'    => $id,
                'error'       => $e->getMessage(),
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

    /** Count weekdays (Mon–Fri) between two dates, inclusive. */
    private function countWorkingDays(Carbon $start, Carbon $end): int
    {
        $days    = 0;
        $current = $start->copy()->startOfDay();

        while ($current->lte($end)) {
            if ($current->isWeekday()) {
                $days++;
            }
            $current->addDay();
        }

        return $days;
    }
}
