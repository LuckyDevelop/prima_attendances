<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\LeaveStatus;
use App\Models\PermissionRequest;
use App\Repositories\Contracts\PermissionRequestRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class PermissionRequestRepository implements PermissionRequestRepositoryInterface
{
    public function paginateByUser(int $userId, array $filters = []): LengthAwarePaginator
    {
        $query = PermissionRequest::where('user_id', $userId)
            ->orderByDesc('request_date')
            ->orderByDesc('submitted_at');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['type'])) {
            $query->where('permission_type', $filters['type']);
        }

        if (!empty($filters['year'])) {
            $query->whereYear('request_date', $filters['year']);
        }

        $perPage = min((int) ($filters['per_page'] ?? 15), 100);

        return $query->paginate($perPage);
    }

    public function find(int $id): ?PermissionRequest
    {
        return PermissionRequest::with(['approvals.approver'])->find($id);
    }

    public function findForApproval(int $id): ?PermissionRequest
    {
        return PermissionRequest::with(['user'])->find($id);
    }

    public function updateStatus(PermissionRequest $permission, LeaveStatus $status): PermissionRequest
    {
        $permission->update(['status' => $status]);

        return $permission;
    }

    public function create(int $userId, array $data): PermissionRequest
    {
        return PermissionRequest::create([
            'user_id'         => $userId,
            'permission_type' => $data['permission_type'],
            'request_date'    => $data['request_date'],
            'start_time'      => $data['start_time'] ?? null,
            'end_time'        => $data['end_time'] ?? null,
            'reason'          => $data['reason'] ?? null,
            'attachment'      => $data['attachment_path'] ?? null,
            'status'          => LeaveStatus::PENDING,
            'submitted_at'    => now(),
        ]);
    }

    public function cancel(PermissionRequest $permission): bool
    {
        return $permission->delete();
    }

    public function existsForDateAndType(int $userId, string $date, string $type): bool
    {
        return PermissionRequest::where('user_id', $userId)
            ->where('request_date', $date)
            ->where('permission_type', $type)
            ->whereIn('status', [LeaveStatus::PENDING->value, LeaveStatus::APPROVED->value])
            ->exists();
    }

    public function paginateForAdmin(int $companyId, array $filters = []): LengthAwarePaginator
    {
        $query = PermissionRequest::query()
            ->join('users', 'permission_requests.user_id', '=', 'users.id')
            ->where('users.company_id', $companyId)
            ->with(['user.department', 'approvals'])
            ->select('permission_requests.*')
            ->orderByDesc('permission_requests.request_date')
            ->orderByDesc('permission_requests.submitted_at');

        if (!empty($filters['status'])) {
            $query->where('permission_requests.status', $filters['status']);
        }

        if (!empty($filters['permission_type'])) {
            $query->where('permission_requests.permission_type', $filters['permission_type']);
        }

        if (!empty($filters['department_id'])) {
            $query->where('users.department_id', $filters['department_id']);
        }

        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('users.full_name', 'like', $search)
                  ->orWhere('users.employee_id', 'like', $search);
            });
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('permission_requests.request_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('permission_requests.request_date', '<=', $filters['date_to']);
        }

        if (!empty($filters['dept_ids'])) {
            $query->whereIn('users.department_id', $filters['dept_ids']);
        }

        $perPage = min((int) ($filters['per_page'] ?? 15), 100);

        return $query->paginate($perPage);
    }
}
