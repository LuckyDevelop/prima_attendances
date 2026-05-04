<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Shift;
use App\Models\ShiftAssignment;
use Illuminate\Database\Eloquent\Collection;

interface ShiftRepositoryInterface
{
    public function getTodayAssignment(int $userId): ?ShiftAssignment;

    /** Returns shift assignments for a given month (format: "YYYY-MM"). */
    public function getSchedule(int $userId, string $month): Collection;

    public function getAllByCompany(int $companyId): Collection;

    public function findShiftById(int $id): ?Shift;

    public function createShift(array $data): Shift;

    public function updateShift(Shift $shift, array $data): Shift;

    public function deleteShift(Shift $shift): bool;

    /** Get all shift assignments for active employees in a company for a given date range. */
    public function getWeekAssignments(int $companyId, string $dateFrom, string $dateTo, ?int $departmentId = null): Collection;

    /** Create or update a single assignment for a user on a specific date. */
    public function upsertAssignment(int $userId, int $shiftId, string $workDate): ShiftAssignment;

    /** Remove a single assignment for a user on a specific date. */
    public function removeAssignment(int $userId, string $workDate): bool;

    /** Bulk create or update assignments. Each item: ['user_id', 'shift_id', 'work_date']. Returns count. */
    public function bulkUpsertAssignments(array $assignments): int;
}
