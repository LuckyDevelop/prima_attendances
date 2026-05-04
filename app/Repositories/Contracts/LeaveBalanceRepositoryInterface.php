<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\LeaveBalance;
use Illuminate\Database\Eloquent\Collection;

interface LeaveBalanceRepositoryInterface
{
    public function getByUserAndYear(int $userId, int $year): Collection;

    public function findByUserLeaveTypeAndYear(int $userId, int $leaveTypeId, int $year): ?LeaveBalance;

    public function deductBalance(int $userId, int $leaveTypeId, int $year, int $days): void;

    /** Get paginated employee balances for admin view. */
    public function paginateByCompany(int $companyId, int $year, array $filters = []): \Illuminate\Pagination\LengthAwarePaginator;

    /** Update total_quota and recalculate remaining for a balance record. */
    public function adjustBalance(LeaveBalance $balance, int $totalQuota): LeaveBalance;
}
