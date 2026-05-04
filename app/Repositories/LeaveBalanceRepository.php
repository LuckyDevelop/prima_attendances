<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\LeaveBalance;
use App\Models\User;
use App\Repositories\Contracts\LeaveBalanceRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class LeaveBalanceRepository implements LeaveBalanceRepositoryInterface
{
    public function getByUserAndYear(int $userId, int $year): Collection
    {
        return LeaveBalance::where('user_id', $userId)
            ->where('year', $year)
            ->with('leaveType')
            ->get();
    }

    public function findByUserLeaveTypeAndYear(int $userId, int $leaveTypeId, int $year): ?LeaveBalance
    {
        return LeaveBalance::where('user_id', $userId)
            ->where('leave_type_id', $leaveTypeId)
            ->where('year', $year)
            ->first();
    }

    public function deductBalance(int $userId, int $leaveTypeId, int $year, int $days): void
    {
        LeaveBalance::where('user_id', $userId)
            ->where('leave_type_id', $leaveTypeId)
            ->where('year', $year)
            ->update([
                'used'      => DB::raw("used + {$days}"),
                'remaining' => DB::raw("remaining - {$days}"),
            ]);
    }

    public function paginateByCompany(int $companyId, int $year, array $filters = []): LengthAwarePaginator
    {
        $userQuery = User::where('company_id', $companyId)
            ->where('status', 'active')
            ->with([
                'leaveBalances' => fn ($q) => $q->where('year', $year)->with('leaveType'),
            ])
            ->orderBy('full_name');

        if (!empty($filters['department_id'])) {
            $userQuery->where('department_id', $filters['department_id']);
        }

        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $userQuery->where(function ($q) use ($search) {
                $q->where('full_name', 'like', $search)
                  ->orWhere('employee_id', 'like', $search);
            });
        }

        $perPage = min((int) ($filters['per_page'] ?? 20), 100);

        return $userQuery->paginate($perPage);
    }

    public function adjustBalance(LeaveBalance $balance, int $totalQuota): LeaveBalance
    {
        $balance->update([
            'total_quota' => $totalQuota,
            'remaining'   => max(0, $totalQuota - $balance->used),
        ]);

        return $balance->fresh();
    }
}
