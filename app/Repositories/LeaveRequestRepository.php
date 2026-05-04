<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\LeaveStatus;
use App\Enums\UserRole;
use App\Models\Department;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Repositories\Contracts\LeaveRequestRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;


class LeaveRequestRepository implements LeaveRequestRepositoryInterface
{
    public function paginateByUser(int $userId, array $filters = []): LengthAwarePaginator
    {
        $query = LeaveRequest::where('user_id', $userId)
            ->with(['leaveType'])
            ->orderByDesc('submitted_at');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['year'])) {
            $query->whereYear('start_date', $filters['year']);
        }

        $perPage = min((int) ($filters['per_page'] ?? 15), 100);

        return $query->paginate($perPage);
    }

    public function find(int $id): ?LeaveRequest
    {
        return LeaveRequest::with(['leaveType', 'approvals.approver'])->find($id);
    }

    public function findForApproval(int $id): ?LeaveRequest
    {
        return LeaveRequest::with(['user', 'leaveType'])->find($id);
    }

    public function updateStatus(LeaveRequest $leave, LeaveStatus $status): LeaveRequest
    {
        $leave->update(['status' => $status]);

        return $leave;
    }

    public function create(int $userId, array $data): LeaveRequest
    {
        return LeaveRequest::create([
            'user_id'       => $userId,
            'leave_type_id' => $data['leave_type_id'],
            'start_date'    => $data['start_date'],
            'end_date'      => $data['end_date'],
            'total_days'    => $data['total_days'],
            'reason'        => $data['reason'] ?? null,
            'attachment'    => $data['attachment_path'] ?? null,
            'status'        => LeaveStatus::PENDING,
            'submitted_at'  => now(),
        ]);
    }

    public function cancel(LeaveRequest $leave): LeaveRequest
    {
        $leave->delete();

        return $leave;
    }

    public function countOnLeaveToday(int $companyId): int
    {
        return LeaveRequest::query()
            ->join('users', 'leave_requests.user_id', '=', 'users.id')
            ->where('users.company_id', $companyId)
            ->where('leave_requests.status', LeaveStatus::APPROVED)
            ->whereDate('leave_requests.start_date', '<=', today())
            ->whereDate('leave_requests.end_date', '>=', today())
            ->count();
    }

    public function paginateForAdmin(int $companyId, array $filters = []): LengthAwarePaginator
    {
        $query = LeaveRequest::query()
            ->join('users', 'leave_requests.user_id', '=', 'users.id')
            ->where('users.company_id', $companyId)
            ->with(['user.department', 'leaveType', 'approvals'])
            ->select('leave_requests.*')
            ->orderByDesc('leave_requests.submitted_at');

        if (!empty($filters['status'])) {
            $query->where('leave_requests.status', $filters['status']);
        }

        if (!empty($filters['leave_type_id'])) {
            $query->where('leave_requests.leave_type_id', $filters['leave_type_id']);
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
            $query->whereDate('leave_requests.start_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('leave_requests.end_date', '<=', $filters['date_to']);
        }

        if (!empty($filters['dept_ids'])) {
            $query->whereIn('users.department_id', $filters['dept_ids']);
        }

        $perPage = min((int) ($filters['per_page'] ?? 15), 100);

        return $query->paginate($perPage);
    }

    public function getLeaveReport(int $companyId, array $filters = []): Collection
    {
        $query = LeaveRequest::query()
            ->join('users', 'leave_requests.user_id', '=', 'users.id')
            ->leftJoin('departments', 'users.department_id', '=', 'departments.id')
            ->join('leave_types', 'leave_requests.leave_type_id', '=', 'leave_types.id')
            ->where('users.company_id', $companyId)
            ->select(
                'leave_requests.user_id',
                'users.full_name',
                'users.employee_id',
                'departments.name as department_name',
                'leave_types.name as leave_type_name',
                DB::raw("SUM(CASE WHEN leave_requests.status = 'approved' THEN leave_requests.total_days ELSE 0 END) as approved_days"),
                DB::raw("SUM(CASE WHEN leave_requests.status = 'pending' THEN leave_requests.total_days ELSE 0 END) as pending_days"),
                DB::raw("SUM(CASE WHEN leave_requests.status = 'rejected' THEN leave_requests.total_days ELSE 0 END) as rejected_days"),
                DB::raw('COUNT(*) as total_requests'),
            )
            ->groupBy(
                'leave_requests.user_id',
                'users.full_name',
                'users.employee_id',
                'departments.name',
                'leave_types.name',
            )
            ->orderBy('users.full_name')
            ->orderBy('leave_types.name');

        if (!empty($filters['date_from'])) {
            $query->whereDate('leave_requests.start_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('leave_requests.end_date', '<=', $filters['date_to']);
        }

        if (!empty($filters['department_id'])) {
            $query->where('users.department_id', $filters['department_id']);
        }

        if (!empty($filters['leave_type_id'])) {
            $query->where('leave_requests.leave_type_id', $filters['leave_type_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('leave_requests.status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('users.full_name', 'like', $search)
                  ->orWhere('users.employee_id', 'like', $search);
            });
        }

        return $query->get();
    }

    public function getRecentPendingForApprover(User $approver, int $limit = 5): Collection
    {
        $query = LeaveRequest::query()
            ->with(['user.department', 'leaveType'])
            ->where('status', LeaveStatus::PENDING)
            ->orderByDesc('submitted_at');

        if ($approver->role === UserRole::MANAGER) {
            $deptIds = Department::where('manager_id', $approver->id)
                ->where('company_id', $approver->company_id)
                ->pluck('id');

            $query->whereHas('user', fn ($q) => $q->whereIn('department_id', $deptIds));
        } else {
            $query->whereHas('user', fn ($q) => $q->where('company_id', $approver->company_id));
        }

        return $query->limit($limit)->get();
    }
}
