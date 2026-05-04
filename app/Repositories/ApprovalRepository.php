<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\ApprovalDecision;
use App\Enums\ApprovalRequestType;
use App\Enums\LeaveStatus;
use App\Enums\UserRole;
use App\Models\Approval;
use App\Models\Department;
use App\Models\LeaveRequest;
use App\Models\PermissionRequest;
use App\Models\User;
use App\Repositories\Contracts\ApprovalRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class ApprovalRepository implements ApprovalRepositoryInterface
{
    public function getPendingLeaveRequests(User $approver, array $filters): LengthAwarePaginator
    {
        $query = LeaveRequest::with(['user', 'leaveType'])
            ->where('status', LeaveStatus::PENDING);

        if ($approver->role === UserRole::MANAGER) {
            $deptIds = Department::where('manager_id', $approver->id)
                ->where('company_id', $approver->company_id)
                ->pluck('id');

            $query->whereHas('user', fn ($q) => $q->whereIn('department_id', $deptIds));
        } else {
            $query->whereHas('user', fn ($q) => $q->where('company_id', $approver->company_id));
        }

        $perPage = min((int) ($filters['per_page'] ?? 15), 100);

        return $query->orderByDesc('submitted_at')->paginate($perPage);
    }

    public function getPendingPermissionRequests(User $approver, array $filters): LengthAwarePaginator
    {
        $query = PermissionRequest::with(['user'])
            ->where('status', LeaveStatus::PENDING);

        if ($approver->role === UserRole::MANAGER) {
            $deptIds = Department::where('manager_id', $approver->id)
                ->where('company_id', $approver->company_id)
                ->pluck('id');

            $query->whereHas('user', fn ($q) => $q->whereIn('department_id', $deptIds));
        } else {
            $query->whereHas('user', fn ($q) => $q->where('company_id', $approver->company_id));
        }

        $perPage = min((int) ($filters['per_page'] ?? 15), 100);

        return $query->orderByDesc('submitted_at')->paginate($perPage);
    }

    public function paginateForAdmin(User $approver, array $filters = []): LengthAwarePaginator
    {
        $deptIds = null;
        if ($approver->role === UserRole::MANAGER) {
            $deptIds = Department::where('manager_id', $approver->id)
                ->where('company_id', $approver->company_id)
                ->pluck('id');
        }

        $requestType = $filters['request_type'] ?? 'leave';
        $status      = $filters['status'] ?? 'pending';
        $departmentId = $filters['department_id'] ?? null;
        $perPage     = min((int) ($filters['per_page'] ?? 15), 100);

        if ($requestType === 'permission') {
            $query = PermissionRequest::query()
                ->with(['user.department'])
                ->join('users', 'permission_requests.user_id', '=', 'users.id')
                ->select('permission_requests.*')
                ->where('permission_requests.status', $status)
                ->orderByDesc('permission_requests.submitted_at');

            if ($deptIds !== null) {
                $query->whereIn('users.department_id', $deptIds);
            } else {
                $query->where('users.company_id', $approver->company_id);
            }

            if ($departmentId) {
                $query->where('users.department_id', $departmentId);
            }

            return $query->paginate($perPage);
        }

        // Default: leave requests
        $query = LeaveRequest::query()
            ->with(['user.department', 'leaveType'])
            ->join('users', 'leave_requests.user_id', '=', 'users.id')
            ->select('leave_requests.*')
            ->where('leave_requests.status', $status)
            ->orderByDesc('leave_requests.submitted_at');

        if ($deptIds !== null) {
            $query->whereIn('users.department_id', $deptIds);
        } else {
            $query->where('users.company_id', $approver->company_id);
        }

        if ($departmentId) {
            $query->where('users.department_id', $departmentId);
        }

        return $query->paginate($perPage);
    }

    public function createApproval(
        ApprovalRequestType $requestType,
        int $requestId,
        User $approver,
        ApprovalDecision $decision,
        ?string $comment
    ): Approval {
        return Approval::create([
            'request_id'   => $requestId,
            'request_type' => $requestType,
            'approver_id'  => $approver->id,
            'level'        => 1,
            'decision'     => $decision,
            'comment'      => $comment,
            'decided_at'   => now(),
        ]);
    }
}
