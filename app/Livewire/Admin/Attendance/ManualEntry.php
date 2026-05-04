<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Attendance;

use App\Enums\AttendanceStatus;
use App\Livewire\Admin\AdminComponent;
use App\Models\AuditLog;
use App\Repositories\Contracts\AttendanceRepositoryInterface;
use App\Repositories\Contracts\OfficeLocationRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

#[Layout('layouts.admin')]
#[Title('Manual Entry Absensi')]
class ManualEntry extends AdminComponent
{
    public string $userId      = '';
    public string $workDate    = '';
    public string $locationId  = '';
    public string $checkInTime = '';
    public string $checkOutTime = '';
    public string $status      = 'present';
    public string $notes       = '';

    protected AttendanceRepositoryInterface $attendanceRepo;
    protected UserRepositoryInterface $userRepo;
    protected OfficeLocationRepositoryInterface $locationRepo;

    public function boot(
        AttendanceRepositoryInterface $attendanceRepo,
        UserRepositoryInterface $userRepo,
        OfficeLocationRepositoryInterface $locationRepo,
    ): void {
        $this->attendanceRepo = $attendanceRepo;
        $this->userRepo       = $userRepo;
        $this->locationRepo   = $locationRepo;
    }

    public function mount(): void
    {
        // Pre-fill from query string (e.g. coming from Show page)
        $this->userId   = request()->query('user_id', '');
        $this->workDate = request()->query('date', today()->toDateString());
    }

    protected function rules(): array
    {
        return [
            'userId'       => ['required', 'exists:users,id'],
            'workDate'     => ['required', 'date', 'before_or_equal:today'],
            'locationId'   => ['required', 'exists:office_locations,id'],
            'checkInTime'  => ['nullable', 'date_format:H:i'],
            'checkOutTime' => ['nullable', 'date_format:H:i', 'after:checkInTime'],
            'status'       => ['required', 'in:present,absent,leave'],
            'notes'        => ['required', 'string', 'max:500'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'userId'      => 'karyawan',
            'workDate'    => 'tanggal',
            'locationId'  => 'lokasi kantor',
            'notes'       => 'catatan',
        ];
    }

    public function save(): void
    {
        $this->validate();

        $user = $this->userRepo->findById((int) $this->userId);

        if (! $user || $user->company_id !== $this->authUser()->company_id) {
            $this->addError('userId', 'Karyawan tidak ditemukan.');
            return;
        }

        $before = null;

        DB::beginTransaction();
        try {
            $data = [
                'office_location_id' => (int) $this->locationId,
                'status'             => $this->status,
                'notes'              => $this->notes,
            ];

            if ($this->checkInTime) {
                $data['check_in_time'] = $this->workDate . ' ' . $this->checkInTime . ':00';
            }

            if ($this->checkOutTime) {
                $data['check_out_time'] = $this->workDate . ' ' . $this->checkOutTime . ':00';
            }

            if ($this->checkInTime && $this->checkOutTime) {
                $checkIn  = \Carbon\Carbon::parse($data['check_in_time']);
                $checkOut = \Carbon\Carbon::parse($data['check_out_time']);
                $data['work_duration_min'] = max(0, $checkOut->diffInMinutes($checkIn));
            }

            $existingAtt = \App\Models\Attendance::where('user_id', (int) $this->userId)
                ->where('work_date', $this->workDate)
                ->first();

            $before = $existingAtt?->toArray();

            $attendance = $this->attendanceRepo->upsert(
                (int) $this->userId,
                $this->workDate,
                $data
            );

            AuditLog::create([
                'user_id'    => $this->authUser()->id,
                'action'     => $before ? 'attendance.manual_update' : 'attendance.manual_create',
                'entity'     => 'attendance',
                'entity_id'  => (string) $attendance->getKey(),
                'metadata'   => ['before' => $before, 'after' => $attendance->toArray()],
                'ip_address' => request()->ip(),
            ]);

            DB::commit();

            $this->dispatch('toast', type: 'success', message: 'Data absensi berhasil disimpan.');
            $this->reset(['checkInTime', 'checkOutTime', 'notes']);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Manual attendance entry failed', [
                'admin_id' => $this->authUser()->id,
                'error'    => $e->getMessage(),
            ]);

            $this->dispatch('toast', type: 'error', message: 'Gagal menyimpan data absensi. Silakan coba lagi.');
        }
    }

    public function getEmployees(): Collection
    {
        return $this->userRepo->getAllActive($this->authUser()->company_id);
    }

    public function getLocations(): Collection
    {
        return $this->locationRepo->getByCompany($this->authUser()->company_id);
    }

    public function render(): View
    {
        return view('livewire.admin.attendance.manual-entry', [
            'employees' => $this->getEmployees(),
            'locations' => $this->getLocations(),
            'statuses'  => AttendanceStatus::cases(),
        ]);
    }
}
