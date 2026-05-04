<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\UserStatus;
use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Models\User;
use App\Repositories\Contracts\ShiftRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class ShiftRepository implements ShiftRepositoryInterface
{
    public function getTodayAssignment(int $userId): ?ShiftAssignment
    {
        return ShiftAssignment::with('shift')
            ->where('user_id', $userId)
            ->whereDate('work_date', today())
            ->first();
    }

    public function getSchedule(int $userId, string $month): Collection
    {
        [$year, $monthNum] = explode('-', $month);

        return ShiftAssignment::with('shift')
            ->where('user_id', $userId)
            ->whereYear('work_date', $year)
            ->whereMonth('work_date', $monthNum)
            ->orderBy('work_date')
            ->get();
    }

    public function getAllByCompany(int $companyId): Collection
    {
        return Shift::where('company_id', $companyId)
            ->orderBy('name')
            ->get();
    }

    public function findShiftById(int $id): ?Shift
    {
        return Shift::find($id);
    }

    public function createShift(array $data): Shift
    {
        return Shift::create($data);
    }

    public function updateShift(Shift $shift, array $data): Shift
    {
        $shift->update($data);

        return $shift->fresh();
    }

    public function deleteShift(Shift $shift): bool
    {
        return $shift->delete();
    }

    public function getWeekAssignments(int $companyId, string $dateFrom, string $dateTo, ?int $departmentId = null): Collection
    {
        return ShiftAssignment::with(['shift', 'user.department'])
            ->whereHas('user', function ($q) use ($companyId, $departmentId) {
                $q->where('company_id', $companyId)
                    ->where('status', UserStatus::ACTIVE);
                if ($departmentId) {
                    $q->where('department_id', $departmentId);
                }
            })
            ->whereBetween('work_date', [$dateFrom, $dateTo])
            ->get();
    }

    public function upsertAssignment(int $userId, int $shiftId, string $workDate): ShiftAssignment
    {
        return ShiftAssignment::updateOrCreate(
            ['user_id' => $userId, 'work_date' => $workDate],
            ['shift_id' => $shiftId]
        );
    }

    public function removeAssignment(int $userId, string $workDate): bool
    {
        return ShiftAssignment::where('user_id', $userId)
            ->whereDate('work_date', $workDate)
            ->delete() > 0;
    }

    public function bulkUpsertAssignments(array $assignments): int
    {
        $count = 0;
        foreach ($assignments as $item) {
            ShiftAssignment::updateOrCreate(
                ['user_id' => $item['user_id'], 'work_date' => $item['work_date']],
                ['shift_id' => $item['shift_id']]
            );
            $count++;
        }

        return $count;
    }
}
