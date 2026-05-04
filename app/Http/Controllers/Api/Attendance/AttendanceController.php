<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Attendance;

use App\Helpers\GeolocationHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Attendance\CheckInRequest;
use App\Http\Requests\Api\Attendance\CheckOutRequest;
use App\Http\Resources\Api\AttendanceResource;
use App\Http\Resources\Api\OfficeLocationResource;
use App\Models\AuditLog;
use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\ShiftAssignment;
use App\Repositories\Contracts\AttendanceRepositoryInterface;
use App\Repositories\Contracts\OfficeLocationRepositoryInterface;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AttendanceController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly AttendanceRepositoryInterface $attendanceRepository,
        private readonly OfficeLocationRepositoryInterface $officeLocationRepository,
    ) {}

    public function today(Request $request): JsonResponse
    {
        $user = $request->user();
        $attendance = $this->attendanceRepository->getTodayAttendance($user->id);

        if ($attendance === null) {
            return $this->success([
                'work_date'   => today()->format('Y-m-d'),
                'check_in'    => null,
                'check_out'   => null,
                'can_check_in' => true,
                'can_check_out' => false,
            ], 'Belum melakukan absensi hari ini.');
        }

        return $this->success(new AttendanceResource($attendance), 'Data absensi hari ini.');
    }

    public function checkIn(CheckInRequest $request): JsonResponse
    {
        $user = $request->user();

        $existingAttendance = $this->attendanceRepository->getTodayAttendance($user->id);
        if ($existingAttendance !== null) {
            return $this->validationError([], 'Anda sudah melakukan check-in hari ini.');
        }

        $officeLocation = $this->officeLocationRepository->getById($request->office_location_id);
        if (!$officeLocation || !$officeLocation->is_active) {
            return $this->notFound('Lokasi kantor tidak ditemukan atau tidak aktif.');
        }

        if (!GeolocationHelper::isWithinRadius(
            (float) $request->latitude,
            (float) $request->longitude,
            (float) $officeLocation->latitude,
            (float) $officeLocation->longitude,
            $officeLocation->radius_meters,
        )) {
            $distance = GeolocationHelper::distanceInMeters(
                (float) $request->latitude,
                (float) $request->longitude,
                (float) $officeLocation->latitude,
                (float) $officeLocation->longitude,
            );

            return $this->validationError([],
                sprintf('Lokasi Anda di luar radius kantor (jarak: %.0fm, max: %dm)',
                    $distance, $officeLocation->radius_meters));
        }

        if ($request->is_mock_location) {
            Log::warning('Mock location detected', [
                'user_id' => $user->id,
                'office_location_id' => $request->office_location_id,
            ]);
        }

        $holiday = Holiday::where('company_id', $user->company_id)
            ->whereDate('date', today())
            ->first();

        DB::beginTransaction();
        try {
            $selfieDir = "selfies/{$user->id}";
            $selfiePath = $request->file('selfie')?->storeAs(
                $selfieDir,
                today()->format('Y-m-d') . '_checkin.jpg',
                'public'
            );

            $attendance = $this->attendanceRepository->checkIn($user->id, array_merge(
                $request->validated(),
                ['selfie_path' => $selfiePath],
            ));

            AuditLog::create([
                'user_id'    => $user->id,
                'action'     => 'check_in',
                'entity'     => 'Attendance',
                'entity_id'  => (string) $attendance->id,
                'metadata'   => ['office_location_id' => $request->office_location_id],
                'ip_address' => $request->ip(),
            ]);

            DB::commit();

            return $this->success(new AttendanceResource($attendance), 'Check-in berhasil.', 201);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Check-in failed', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);

            return $this->serverError();
        }
    }

    public function checkOut(CheckOutRequest $request): JsonResponse
    {
        $user = $request->user();

        $attendance = $this->attendanceRepository->getTodayAttendance($user->id);
        if ($attendance === null) {
            return $this->validationError([], 'Anda belum melakukan check-in hari ini.');
        }

        if ($attendance->check_out_time !== null) {
            return $this->validationError([], 'Anda sudah melakukan check-out hari ini.');
        }

        DB::beginTransaction();
        try {
            $selfieDir = "selfies/{$user->id}";
            $selfiePath = $request->file('selfie')?->storeAs(
                $selfieDir,
                today()->format('Y-m-d') . '_checkout.jpg',
                'public'
            );

            $attendance = $this->attendanceRepository->checkOut($attendance, array_merge(
                $request->validated(),
                ['selfie_path' => $selfiePath],
            ));

            AuditLog::create([
                'user_id'    => $user->id,
                'action'     => 'check_out',
                'entity'     => 'Attendance',
                'entity_id'  => (string) $attendance->id,
                'ip_address' => $request->ip(),
            ]);

            DB::commit();

            return $this->success(new AttendanceResource($attendance), 'Check-out berhasil.');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Check-out failed', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);

            return $this->serverError();
        }
    }

    public function history(Request $request): JsonResponse
    {
        $filters = [
            'month'    => $request->query('month'),
            'status'   => $request->query('status'),
            'per_page' => $request->query('per_page', 15),
        ];

        $paginator = $this->attendanceRepository->paginateUserHistory($request->user()->id, $filters);
        $items = AttendanceResource::collection($paginator->items())->collection;
        $paginator->setCollection($items);

        return $this->successPaginated($paginator, 'Riwayat absensi berhasil diambil.');
    }

    public function locations(Request $request): JsonResponse
    {
        $locations = $this->officeLocationRepository->getByCompany($request->user()->company_id);

        return $this->success(
            OfficeLocationResource::collection($locations),
            'Lokasi kantor berhasil diambil.'
        );
    }
}
