<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Attendance;
use App\Models\OfficeLocation;
use App\Models\ShiftAssignment;
use App\Repositories\Contracts\AttendanceRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AttendanceRepository implements AttendanceRepositoryInterface
{
    public function getTodayAttendance(int $userId): ?Attendance
    {
        return Attendance::where('user_id', $userId)
            ->whereDate('work_date', today())
            ->with(['user', 'officeLocation'])
            ->first();
    }

    public function checkIn(int $userId, array $data): Attendance
    {
        return Attendance::create([
            'user_id'            => $userId,
            'office_location_id' => $data['office_location_id'],
            'work_date'          => today(),
            'check_in_time'      => now(),
            'check_in_lat'       => $data['latitude'],
            'check_in_lng'       => $data['longitude'],
            'check_in_selfie'    => $data['selfie_path'] ?? null,
            'face_verified'      => $data['face_verified'] ?? false,
            'is_mock_location'   => $data['is_mock_location'] ?? false,
            'status'             => 'present',
            'notes'              => $data['notes'] ?? null,
        ]);
    }

    public function checkOut(Attendance $attendance, array $data): Attendance
    {
        $attendance->update([
            'check_out_time'    => now(),
            'check_out_lat'     => $data['latitude'],
            'check_out_lng'     => $data['longitude'],
            'check_out_selfie'  => $data['selfie_path'] ?? null,
            'is_mock_location'  => $data['is_mock_location'] ?? false,
            'face_verified'     => $data['face_verified'] ?? false,
            'notes'             => $data['notes'] ?? null,
        ]);

        $this->calculateWorkDuration($attendance);

        return $attendance;
    }

    public function paginateUserHistory(int $userId, array $filters = []): LengthAwarePaginator
    {
        $query = Attendance::where('user_id', $userId)
            ->with(['officeLocation'])
            ->orderByDesc('work_date');

        if (!empty($filters['month'])) {
            $query->whereYearMonth('work_date', $filters['month']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->paginate($filters['per_page'] ?? 15);
    }

    public function paginateForAdmin(int $companyId, array $filters = []): LengthAwarePaginator
    {
        $query = Attendance::query()
            ->join('users', 'attendances.user_id', '=', 'users.id')
            ->where('users.company_id', $companyId)
            ->with(['user', 'officeLocation'])
            ->select('attendances.*')
            ->orderByDesc('attendances.work_date')
            ->orderByDesc('attendances.check_in_time');

        if (!empty($filters['user_id'])) {
            $query->where('attendances.user_id', $filters['user_id']);
        }

        if (!empty($filters['department_id'])) {
            $query->where('users.department_id', $filters['department_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('attendances.status', $filters['status']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('attendances.work_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('attendances.work_date', '<=', $filters['date_to']);
        }

        if (!empty($filters['month'])) {
            $query->whereYearMonth('attendances.work_date', $filters['month']);
        }

        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('users.full_name', 'like', $search)
                  ->orWhere('users.employee_id', 'like', $search);
            });
        }

        if (!empty($filters['late_only'])) {
            $query->join('shift_assignments as sa_late', function ($join) {
                $join->on('sa_late.user_id', '=', 'attendances.user_id')
                     ->whereColumn('sa_late.work_date', 'attendances.work_date');
            })
            ->join('shifts as s_late', 'sa_late.shift_id', '=', 's_late.id')
            ->whereRaw('TIME(attendances.check_in_time) > s_late.start_time');
        }

        if (!empty($filters['mock_only'])) {
            $query->where('attendances.is_mock_location', true);
        }

        if (!empty($filters['manager_id'])) {
            $managerId = $filters['manager_id'];
            $query->whereIn('users.department_id', function ($sub) use ($managerId, $companyId) {
                $sub->select('id')
                    ->from('departments')
                    ->where('manager_id', $managerId)
                    ->where('company_id', $companyId);
            });
        }

        return $query->paginate($filters['per_page'] ?? 15);
    }

    public function findById(int $id): ?Attendance
    {
        return Attendance::with(['user', 'officeLocation'])->find($id);
    }

    public function upsert(int $userId, string $workDate, array $data): Attendance
    {
        return Attendance::updateOrCreate(
            ['user_id' => $userId, 'work_date' => $workDate],
            $data
        );
    }

    public function countPresentToday(int $companyId): int
    {
        return Attendance::query()
            ->join('users', 'attendances.user_id', '=', 'users.id')
            ->where('users.company_id', $companyId)
            ->whereDate('attendances.work_date', today())
            ->whereNotNull('attendances.check_in_time')
            ->count();
    }

    public function countLateToday(int $companyId): int
    {
        return Attendance::query()
            ->join('users', 'attendances.user_id', '=', 'users.id')
            ->where('users.company_id', $companyId)
            ->whereDate('attendances.work_date', today())
            ->whereNotNull('attendances.check_in_time')
            ->join('shift_assignments', function ($join) {
                $join->on('shift_assignments.user_id', '=', 'attendances.user_id')
                     ->whereColumn('shift_assignments.work_date', 'attendances.work_date');
            })
            ->join('shifts', 'shift_assignments.shift_id', '=', 'shifts.id')
            ->whereRaw('TIME(attendances.check_in_time) > shifts.start_time')
            ->count();
    }

    public function getMonthSummary(int $companyId, int $year, int $month, ?int $departmentId = null): Collection
    {
        $query = Attendance::query()
            ->join('users', 'attendances.user_id', '=', 'users.id')
            ->where('users.company_id', $companyId)
            ->whereYear('attendances.work_date', $year)
            ->whereMonth('attendances.work_date', $month)
            ->select(
                'attendances.work_date',
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN attendances.status = 'present' THEN 1 ELSE 0 END) as present"),
                DB::raw("SUM(CASE WHEN attendances.status = 'absent' THEN 1 ELSE 0 END) as absent"),
                DB::raw("SUM(CASE WHEN attendances.status = 'leave' THEN 1 ELSE 0 END) as `leave`"),
            )
            ->groupBy('attendances.work_date')
            ->orderBy('attendances.work_date');

        if ($departmentId) {
            $query->where('users.department_id', $departmentId);
        }

        return $query->get();
    }

    public function getAttendanceReport(int $companyId, array $filters = []): Collection
    {
        $query = Attendance::query()
            ->join('users', 'attendances.user_id', '=', 'users.id')
            ->leftJoin('departments', 'users.department_id', '=', 'departments.id')
            ->where('users.company_id', $companyId)
            ->select(
                'attendances.user_id',
                'users.full_name',
                'users.employee_id',
                'departments.name as department_name',
                DB::raw("SUM(CASE WHEN attendances.status = 'present' THEN 1 ELSE 0 END) as present_count"),
                DB::raw("SUM(CASE WHEN attendances.status = 'absent' THEN 1 ELSE 0 END) as absent_count"),
                DB::raw("SUM(CASE WHEN attendances.status = 'leave' THEN 1 ELSE 0 END) as leave_count"),
                DB::raw('COUNT(*) as total_count'),
                DB::raw('SUM(attendances.work_duration_min) as total_work_min'),
            )
            ->groupBy('attendances.user_id', 'users.full_name', 'users.employee_id', 'departments.name')
            ->orderBy('users.full_name');

        if (!empty($filters['date_from'])) {
            $query->whereDate('attendances.work_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('attendances.work_date', '<=', $filters['date_to']);
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

        // Late count: check_in_time > shift start_time on that day
        return $query->get()->map(function ($row) {
            $row->late_count = Attendance::query()
                ->join('users as u2', 'attendances.user_id', '=', 'u2.id')
                ->join('shift_assignments', function ($j) {
                    $j->on('shift_assignments.user_id', '=', 'attendances.user_id')
                      ->whereColumn('shift_assignments.work_date', 'attendances.work_date');
                })
                ->join('shifts', 'shift_assignments.shift_id', '=', 'shifts.id')
                ->where('attendances.user_id', $row->user_id)
                ->whereRaw('TIME(attendances.check_in_time) > shifts.start_time')
                ->whereNotNull('attendances.check_in_time')
                ->count();

            return $row;
        });
    }

    public function getLateArrivalsReport(int $companyId, array $filters = []): Collection
    {
        $query = Attendance::query()
            ->join('users', 'attendances.user_id', '=', 'users.id')
            ->leftJoin('departments', 'users.department_id', '=', 'departments.id')
            ->join('shift_assignments', function ($j) {
                $j->on('shift_assignments.user_id', '=', 'attendances.user_id')
                  ->whereColumn('shift_assignments.work_date', 'attendances.work_date');
            })
            ->join('shifts', 'shift_assignments.shift_id', '=', 'shifts.id')
            ->where('users.company_id', $companyId)
            ->whereNotNull('attendances.check_in_time')
            ->whereRaw('TIME(attendances.check_in_time) > shifts.start_time')
            ->select(
                'attendances.user_id',
                'users.full_name',
                'users.employee_id',
                'departments.name as department_name',
                'attendances.work_date',
                'attendances.check_in_time',
                'shifts.name as shift_name',
                'shifts.start_time as shift_start_time',
                DB::raw('TIMESTAMPDIFF(MINUTE, CONCAT(DATE(attendances.check_in_time), " ", shifts.start_time), attendances.check_in_time) as late_minutes'),
            )
            ->orderByDesc('attendances.work_date')
            ->orderBy('users.full_name');

        if (!empty($filters['date_from'])) {
            $query->whereDate('attendances.work_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('attendances.work_date', '<=', $filters['date_to']);
        }

        if (!empty($filters['department_id'])) {
            $query->where('users.department_id', $filters['department_id']);
        }

        if (!empty($filters['min_late_minutes'])) {
            $query->havingRaw('late_minutes >= ?', [(int) $filters['min_late_minutes']]);
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

    public function getWorkingHoursReport(int $companyId, array $filters = []): Collection
    {
        $query = Attendance::query()
            ->join('users', 'attendances.user_id', '=', 'users.id')
            ->leftJoin('departments', 'users.department_id', '=', 'departments.id')
            ->where('users.company_id', $companyId)
            ->whereNotNull('attendances.check_in_time')
            ->select(
                'attendances.user_id',
                'users.full_name',
                'users.employee_id',
                'departments.name as department_name',
                DB::raw('COUNT(*) as work_days'),
                DB::raw('SUM(attendances.work_duration_min) as total_work_min'),
                DB::raw('AVG(NULLIF(attendances.work_duration_min, 0)) as avg_work_min'),
            )
            ->groupBy('attendances.user_id', 'users.full_name', 'users.employee_id', 'departments.name')
            ->orderBy('users.full_name');

        if (!empty($filters['date_from'])) {
            $query->whereDate('attendances.work_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('attendances.work_date', '<=', $filters['date_to']);
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

        return $query->get();
    }

    private function calculateWorkDuration(Attendance $attendance): void
    {
        if ($attendance->check_in_time === null || $attendance->check_out_time === null) {
            return;
        }

        $checkInTime = $attendance->check_in_time;
        $checkOutTime = $attendance->check_out_time;

        $diffMinutes = $checkOutTime->diffInMinutes($checkInTime);

        $shift = ShiftAssignment::where('user_id', $attendance->user_id)
            ->whereDate('work_date', $attendance->work_date)
            ->with('shift')
            ->first();

        $breakMinutes = $shift?->shift?->break_minutes ?? 0;
        $workDurationMin = max(0, $diffMinutes - $breakMinutes);

        $attendance->update(['work_duration_min' => $workDurationMin]);
    }
}
