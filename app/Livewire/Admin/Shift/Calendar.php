<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Shift;

use App\Livewire\Admin\AdminComponent;
use App\Repositories\Contracts\DepartmentRepositoryInterface;
use App\Repositories\Contracts\ShiftRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;

#[Layout('layouts.admin')]
#[Title('Jadwal Shift')]
class Calendar extends AdminComponent
{
    #[Url]
    public string $weekStart = '';

    #[Url]
    public string $departmentId = '';

    /** Single assignment modal state. */
    public int    $assignUserId  = 0;
    public string $assignDate    = '';
    public string $assignShiftId = '';
    public string $assignUserName = '';

    /** Bulk assignment modal state. */
    public array  $bulkUserIds   = [];
    public string $bulkDateFrom  = '';
    public string $bulkDateTo    = '';
    public string $bulkShiftId   = '';
    public string $bulkPattern   = 'weekdays'; // weekdays | all
    public bool   $bulkPreviewReady = false;
    public int    $bulkPreviewCount = 0;

    protected ShiftRepositoryInterface $shiftRepo;
    protected UserRepositoryInterface  $userRepo;
    protected DepartmentRepositoryInterface $deptRepo;

    public function boot(
        ShiftRepositoryInterface      $shiftRepo,
        UserRepositoryInterface       $userRepo,
        DepartmentRepositoryInterface $deptRepo,
    ): void {
        $this->shiftRepo = $shiftRepo;
        $this->userRepo  = $userRepo;
        $this->deptRepo  = $deptRepo;
    }

    public function mount(): void
    {
        if ($this->weekStart === '') {
            $this->weekStart = now()->startOfWeek(Carbon::MONDAY)->toDateString();
        }
        $this->bulkDateFrom = $this->weekStart;
        $this->bulkDateTo   = Carbon::parse($this->weekStart)->endOfWeek(Carbon::SUNDAY)->toDateString();
    }

    public function updatedDepartmentId(): void
    {
        // No pagination to reset, assignments are computed fresh.
    }

    // ─── Week Navigation ────────────────────────────────────────────────────────

    public function previousWeek(): void
    {
        $this->weekStart = Carbon::parse($this->weekStart)->subWeek()->toDateString();
    }

    public function nextWeek(): void
    {
        $this->weekStart = Carbon::parse($this->weekStart)->addWeek()->toDateString();
    }

    public function goToCurrentWeek(): void
    {
        $this->weekStart = now()->startOfWeek(Carbon::MONDAY)->toDateString();
    }

    // ─── Computed Data ──────────────────────────────────────────────────────────

    /** @return Carbon[] */
    public function getWeekDays(): array
    {
        $start = Carbon::parse($this->weekStart);
        $days  = [];
        for ($i = 0; $i < 7; $i++) {
            $days[] = $start->copy()->addDays($i);
        }

        return $days;
    }

    public function getEmployees(): Collection
    {
        $companyId = $this->authUser()->company_id;
        $deptId    = $this->departmentId ? (int) $this->departmentId : null;

        $query = \App\Models\User::with('department')
            ->where('company_id', $companyId)
            ->where('status', \App\Enums\UserStatus::ACTIVE)
            ->orderBy('full_name');

        if ($deptId) {
            $query->where('department_id', $deptId);
        }

        return $query->get();
    }

    /**
     * Returns assignments indexed as [userId][dateStr] => ['id', 'shift_id', 'shift_name', 'start_time', 'end_time'].
     *
     * @return array<int, array<string, array<string, mixed>>>
     */
    public function getAssignmentMap(): array
    {
        $weekEnd     = Carbon::parse($this->weekStart)->addDays(6)->toDateString();
        $assignments = $this->shiftRepo->getWeekAssignments(
            $this->authUser()->company_id,
            $this->weekStart,
            $weekEnd,
            $this->departmentId ? (int) $this->departmentId : null,
        );

        $map = [];
        foreach ($assignments as $assignment) {
            $dateStr = Carbon::parse($assignment->work_date)->toDateString();
            $map[$assignment->user_id][$dateStr] = [
                'id'         => $assignment->id,
                'shift_id'   => $assignment->shift_id,
                'shift_name' => $assignment->shift?->name ?? '?',
                'start_time' => $assignment->shift ? substr($assignment->shift->start_time, 0, 5) : '',
                'end_time'   => $assignment->shift ? substr($assignment->shift->end_time, 0, 5) : '',
            ];
        }

        return $map;
    }

    public function getShifts(): Collection
    {
        return $this->shiftRepo->getAllByCompany($this->authUser()->company_id);
    }

    public function getDepartments(): Collection
    {
        return $this->deptRepo->getByCompany($this->authUser()->company_id);
    }

    // ─── Single Assignment ──────────────────────────────────────────────────────

    public function openAssign(int $userId, string $date): void
    {
        $user = $this->userRepo->findById($userId);

        if (! $user || $user->company_id !== $this->authUser()->company_id) {
            return;
        }

        $this->assignUserId   = $userId;
        $this->assignDate     = $date;
        $this->assignUserName = $user->full_name;
        $this->assignShiftId  = '';

        // Pre-fill with existing assignment if any.
        $weekEnd = Carbon::parse($this->weekStart)->addDays(6)->toDateString();
        $map     = $this->getAssignmentMap();
        if (isset($map[$userId][$date])) {
            $this->assignShiftId = (string) $map[$userId][$date]['shift_id'];
        }

        $this->dispatch('open-modal', name: 'assign-shift');
    }

    public function saveAssignment(): void
    {
        $this->validate([
            'assignUserId'  => ['required', 'integer', 'min:1'],
            'assignDate'    => ['required', 'date'],
            'assignShiftId' => ['nullable', 'integer', 'exists:shifts,id'],
        ]);

        $user = $this->userRepo->findById($this->assignUserId);

        if (! $user || $user->company_id !== $this->authUser()->company_id) {
            $this->dispatch('toast', type: 'error', message: 'Karyawan tidak ditemukan.');
            return;
        }

        if ($this->assignShiftId === '' || $this->assignShiftId === '0') {
            $this->shiftRepo->removeAssignment($this->assignUserId, $this->assignDate);
            $this->dispatch('toast', type: 'success', message: 'Jadwal shift dihapus.');
        } else {
            $this->shiftRepo->upsertAssignment(
                $this->assignUserId,
                (int) $this->assignShiftId,
                $this->assignDate,
            );
            $this->dispatch('toast', type: 'success', message: 'Jadwal shift disimpan.');
        }

        $this->dispatch('close-modal', name: 'assign-shift');
    }

    // ─── Bulk Assignment ────────────────────────────────────────────────────────

    public function openBulk(): void
    {
        $this->bulkUserIds       = [];
        $this->bulkDateFrom      = $this->weekStart;
        $this->bulkDateTo        = Carbon::parse($this->weekStart)->addDays(6)->toDateString();
        $this->bulkShiftId       = '';
        $this->bulkPattern       = 'weekdays';
        $this->bulkPreviewReady  = false;
        $this->bulkPreviewCount  = 0;
        $this->dispatch('open-modal', name: 'bulk-assign');
    }

    public function generateBulkPreview(): void
    {
        $this->validate([
            'bulkUserIds'  => ['required', 'array', 'min:1'],
            'bulkUserIds.*'=> ['integer'],
            'bulkDateFrom' => ['required', 'date'],
            'bulkDateTo'   => ['required', 'date', 'after_or_equal:bulkDateFrom'],
            'bulkShiftId'  => ['required', 'integer', 'exists:shifts,id'],
        ]);

        $dates = $this->buildDateList($this->bulkDateFrom, $this->bulkDateTo, $this->bulkPattern);
        $this->bulkPreviewCount = count($this->bulkUserIds) * count($dates);
        $this->bulkPreviewReady = true;
    }

    public function confirmBulk(): void
    {
        $this->validate([
            'bulkUserIds'  => ['required', 'array', 'min:1'],
            'bulkUserIds.*'=> ['integer'],
            'bulkDateFrom' => ['required', 'date'],
            'bulkDateTo'   => ['required', 'date', 'after_or_equal:bulkDateFrom'],
            'bulkShiftId'  => ['required', 'integer', 'exists:shifts,id'],
        ]);

        $companyId = $this->authUser()->company_id;
        $dates     = $this->buildDateList($this->bulkDateFrom, $this->bulkDateTo, $this->bulkPattern);
        $shiftId   = (int) $this->bulkShiftId;

        // Verify all users belong to this company.
        $validUserIds = \App\Models\User::where('company_id', $companyId)
            ->whereIn('id', $this->bulkUserIds)
            ->pluck('id')
            ->toArray();

        if (empty($validUserIds)) {
            $this->dispatch('toast', type: 'error', message: 'Tidak ada karyawan valid yang dipilih.');
            return;
        }

        $assignments = [];
        foreach ($validUserIds as $userId) {
            foreach ($dates as $date) {
                $assignments[] = ['user_id' => $userId, 'shift_id' => $shiftId, 'work_date' => $date];
            }
        }

        DB::beginTransaction();
        try {
            $count = $this->shiftRepo->bulkUpsertAssignments($assignments);
            DB::commit();

            $this->dispatch('toast', type: 'success', message: "{$count} jadwal shift berhasil disimpan.");
            $this->dispatch('close-modal', name: 'bulk-assign');
            $this->bulkPreviewReady = false;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk shift assignment failed', [
                'admin_id' => $this->authUser()->id,
                'error'    => $e->getMessage(),
            ]);
            $this->dispatch('toast', type: 'error', message: 'Gagal menyimpan jadwal. Silakan coba lagi.');
        }
    }

    /** Build list of date strings between from and to based on pattern. */
    private function buildDateList(string $from, string $to, string $pattern): array
    {
        $dates   = [];
        $current = Carbon::parse($from);
        $end     = Carbon::parse($to);

        while ($current->lte($end)) {
            $include = match ($pattern) {
                'weekdays' => $current->isWeekday(),
                default    => true,
            };

            if ($include) {
                $dates[] = $current->toDateString();
            }

            $current->addDay();
        }

        return $dates;
    }

    public function render(): View
    {
        $weekDays = $this->getWeekDays();
        $employees = $this->getEmployees();
        $assignmentMap = $this->getAssignmentMap();
        $shifts = $this->getShifts();
        $departments = $this->getDepartments();

        return view('livewire.admin.shift.calendar', compact(
            'weekDays',
            'employees',
            'assignmentMap',
            'shifts',
            'departments',
        ));
    }
}
