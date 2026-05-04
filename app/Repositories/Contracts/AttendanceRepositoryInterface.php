<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Attendance;
use App\Models\OfficeLocation;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface AttendanceRepositoryInterface
{
    public function getTodayAttendance(int $userId): ?Attendance;

    public function checkIn(int $userId, array $data): Attendance;

    public function checkOut(Attendance $attendance, array $data): Attendance;

    public function paginateUserHistory(int $userId, array $filters = []): LengthAwarePaginator;

    /** Admin paginated list with advanced filters. */
    public function paginateForAdmin(int $companyId, array $filters = []): LengthAwarePaginator;

    public function findById(int $id): ?Attendance;

    /** Create or update attendance for a user+date (manual entry). */
    public function upsert(int $userId, string $workDate, array $data): Attendance;

    public function countPresentToday(int $companyId): int;

    public function countLateToday(int $companyId): int;

    /**
     * Returns per-day attendance counts for a given month.
     * Each item has: work_date, present, absent, leave, total.
     */
    public function getMonthSummary(int $companyId, int $year, int $month, ?int $departmentId = null): Collection;

    /**
     * Attendance report grouped per employee.
     * Each item: user_id, full_name, employee_id, department, present, late, absent, leave, total_work_min.
     */
    public function getAttendanceReport(int $companyId, array $filters = []): Collection;

    /**
     * Late arrivals report.
     * Each item: user_id, full_name, work_date, check_in_time, shift_start, late_minutes.
     */
    public function getLateArrivalsReport(int $companyId, array $filters = []): Collection;

    /**
     * Working hours report grouped per employee.
     * Each item: user_id, full_name, employee_id, department, work_days, total_work_min.
     */
    public function getWorkingHoursReport(int $companyId, array $filters = []): Collection;
}
