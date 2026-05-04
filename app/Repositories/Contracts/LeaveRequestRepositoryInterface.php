<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Enums\LeaveStatus;
use App\Models\LeaveRequest;
use Illuminate\Pagination\LengthAwarePaginator;

interface LeaveRequestRepositoryInterface
{
    public function paginateByUser(int $userId, array $filters = []): LengthAwarePaginator;

    public function find(int $id): ?LeaveRequest;

    /** Find a leave request with user relation loaded, for approval actions. */
    public function findForApproval(int $id): ?LeaveRequest;

    public function create(int $userId, array $data): LeaveRequest;

    public function updateStatus(LeaveRequest $leave, LeaveStatus $status): LeaveRequest;

    public function cancel(LeaveRequest $leave): LeaveRequest;

    public function countOnLeaveToday(int $companyId): int;

    /** Returns at most $limit pending leaves the given approver can act on, newest first. */
    public function getRecentPendingForApprover(\App\Models\User $approver, int $limit = 5): \Illuminate\Support\Collection;

    /** Paginated list for admin panel with advanced filters. */
    public function paginateForAdmin(int $companyId, array $filters = []): LengthAwarePaginator;

    /**
     * Leave report grouped per employee.
     * Each item: user_id, full_name, employee_id, department, leave_type, total_days, status breakdown.
     */
    public function getLeaveReport(int $companyId, array $filters = []): \Illuminate\Support\Collection;
}
